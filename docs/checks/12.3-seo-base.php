<?php

/**
 * Phase 12.3 check: redirects (match, hit count, loop, chain, cap), 404 monitor, robots.txt.
 * Run: php artisan tinker docs/checks/12.3-seo-base.php   (needs `db:seed` first; everything is rolled back)
 */

use App\Actions\Monitoring\RecordNotFound;
use App\Actions\Redirects\ImportRedirects;
use App\Actions\Redirects\SaveRedirect;
use App\Actions\Redirects\SetRedirectActive;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use App\Models\User;
use App\Support\Redirects\RedirectRules;
use App\Support\Redirects\RuleSet;
use Illuminate\Http\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};
// defer() runs after the response; in the console that would be after the rollback, so run it now.
$handle = function (string $uri) {
    $response = app()->handle(Request::create($uri, 'GET'));
    app(DeferredCallbackCollection::class)->invoke();

    return $response;
};
$actor = User::query()->where('email', 'super-admin@gemcon.test')->firstOrFail();
$save = fn (array $data, ?Redirect $redirect = null) => app(SaveRedirect::class)->handle($actor, $data, $redirect);
$refused = function (callable $fn): ?string {
    try {
        $fn();
    } catch (ValidationException $exception) {
        return $exception->validator->errors()->first();
    }

    return null;
};

DB::beginTransaction();

try {
    // 1. /old → /projects, hit count rises
    $old = $save(['source' => '/old', 'target' => '/projects', 'code' => 301])['redirect'];
    $response = $handle('/old');
    $say('GET /old status', $response->getStatusCode(), 301);
    $say('GET /old location', parse_url($response->headers->get('Location'), PHP_URL_PATH), '/projects');
    $say('GET /Old/ (one hop)', parse_url($handle('/Old/')->headers->get('Location'), PHP_URL_PATH), '/projects');
    $say('GET /old?utm_source=x keeps the query', parse_url($handle('/old?utm_source=x')->headers->get('Location'), PHP_URL_QUERY), 'utm_source=x');
    $say('hits', $old->fresh()->hits, 3); // defer() runs at once in the console

    // 2. A loop is refused with the path
    $loop = $refused(fn () => $save(['source' => '/projects', 'target' => '/old', 'code' => 301]));
    $say('B→A while A→B refused', str_contains((string) $loop, 'circles'), true);
    $say('loop message', $loop, 'This would send visitors in circles: /projects → /old → /projects. Change one of the redirects first.');

    // 3. A chain is collapsed, inbound rules are re-pointed
    $save(['source' => '/older', 'target' => '/old', 'code' => 301]);
    $say('/older collapsed to /projects', Redirect::query()->where('source', '/older')->value('target'), '/projects');
    $result = $save(['source' => '/oldest', 'target' => '/old', 'code' => 302]);
    $say('/oldest collapsed', $result['redirect']->target, '/projects');
    $save(['source' => '/x1', 'target' => '/y1', 'code' => 301]);
    $save(['source' => '/y1', 'target' => '/z1', 'code' => 301]);
    $say('inbound /x1 re-pointed to /z1', Redirect::query()->where('source', '/x1')->value('target'), '/z1');

    // 4. 410 and prefix rules
    $save(['source' => '/gone', 'target' => null, 'code' => 410]);
    $say('GET /gone', $handle('/gone')->getStatusCode(), 410);
    $save(['source' => '/blog/*', 'target' => '/news/*', 'code' => 301]);
    $say('prefix /blog/a/b', parse_url($handle('/blog/a/b')->headers->get('Location'), PHP_URL_PATH), '/news/a/b');
    $say('prefix does not match /blogger', $handle('/blogger')->getStatusCode(), 404);
    $invalid = fn (array $input): bool => Validator::make($input, RedirectRules::rules($input))->fails();
    $say('admin cannot be a source', $invalid(['source' => '/admin/x', 'target' => '/projects', 'code' => 301]), true);
    $say('home cannot be a source', $invalid(['source' => '/', 'target' => '/projects', 'code' => 301]), true);
    $say('duplicate source refused', $invalid(['source' => '/old', 'target' => '/projects', 'code' => 301]), true);
    $say('301 needs a target', $invalid(['source' => '/free', 'target' => '', 'code' => 301]), true);
    $say('same as source refused', $invalid(['source' => '/free', 'target' => '/free', 'code' => 301]), true);

    // 5. Inactive rule does nothing; switching on re-checks loops
    $inactive = $save(['source' => '/off', 'target' => '/projects', 'code' => 301, 'is_active' => false])['redirect'];
    $say('inactive /off', $handle('/off')->getStatusCode(), 404);
    app(SetRedirectActive::class)->handle($actor, $inactive, true);
    $say('switched on /off', $handle('/off')->getStatusCode(), 301);

    // 6. Trash → rule stops, restore → rule works (a loop made meanwhile restores it switched off)
    $trashed = $save(['source' => '/temp', 'target' => '/contact', 'code' => 301])['redirect'];
    $trashed->delete();
    $say('trashed /temp', $handle('/temp')->getStatusCode(), 404);
    $save(['source' => '/contact', 'target' => '/temp', 'code' => 301]);
    $trashed->binResolveConflicts([]);
    $say('restored into a loop → inactive', $trashed->is_active, false);

    // 7. Import
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, "Source,Target,Code,Active\n/imp-a,/projects,301,Yes\n/imp-b/*,/news/*,302,No\n/projects,/imp-a,301,Yes\nbad,/x,301,Yes\n/imp-c,,301,Yes\n");
    $report = app(ImportRedirects::class)->handle($actor, $path);
    $say('import created', $report['created'], 2);
    $say('import skipped (loop, bad address, no target)', $report['skipped'], 3);

    // 8. 404 monitor
    $handle('/no-such-page');
    $handle('/no-such-page');
    $say('404 counted', NotFoundLog::query()->where('path', '/no-such-page')->value('hits'), 2);
    app(RecordNotFound::class)->handle('/wp-login.php', null);
    $say('ignored by pattern', NotFoundLog::query()->where('path', '/wp-login.php')->exists(), false);
    $say('admin 404 not tracked', (function () use ($handle) {
        $handle('/admin/nothing-here');

        return NotFoundLog::query()->where('path', 'like', '/admin%')->exists();
    })(), false);
    $say('rule match for the monitor', RuleSet::current()->match('/old') !== null, true);

    // 9. robots.txt
    $robots = $handle('/robots.txt');
    $say('robots status', $robots->getStatusCode(), 200);
    $say('robots (not production)', $robots->getContent(), "User-agent: *\nDisallow: /\n");
} finally {
    DB::rollBack();
    RuleSet::bump();
}
