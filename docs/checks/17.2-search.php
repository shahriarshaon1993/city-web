<?php

/**
 * Phase 17.2 check: site search (FR-SRCH-010, PG-19, NFR-SEC-10). Scout database engine over the seeded
 * projects, posts and pages: a project name, an area, a word from an article, a word prefix, a title ranking
 * first, max 10 per group, hostile boolean-mode text, the minimum length, drafts staying out, `search_text`
 * kept current on save (and when an area is renamed), and the 429 on the 61st request in a minute.
 * MySQL FULLTEXT only sees committed rows, so everything here searches the seeded data; the rest is rolled back.
 * Run: php docs/checks/17.2-search.php   (needs the demo seeders and `php artisan search:refresh` after the migration)
 */

use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\Post;
use App\Models\Project;
use App\Queries\Site\SiteSearch;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (! function_exists('app')) {
    require __DIR__.'/../../vendor/autoload.php';
    $app = require __DIR__.'/../../bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
}

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

$group = fn (string $query, string $key): ?array => collect(SiteSearch::groups($query))->firstWhere('key', $key);
$status = fn (string $path, string $ip = '10.17.2.1'): int => $app->handle(Request::create($path, 'GET', server: ['REMOTE_ADDR' => $ip]))->getStatusCode();

DB::beginTransaction();

try {
    $project = Project::query()->published()->with('location.parent')->firstOrFail();
    $post = Post::query()->published()->firstOrFail();
    $area = $project->location;

    echo "-- finds\n";
    $say('a project by its name', collect($group($project->name, 'projects')['items'] ?? [])->pluck('name')->contains($project->name), true);
    $say('a project by its area', ($group($area->name, 'projects')['total'] ?? 0) >= 1, true);
    $say('a post by a title word', collect($group(explode(' ', $post->title)[3] ?? $post->title, 'news')['items'] ?? [])->isNotEmpty(), true);
    $say('a word prefix finds the area', ($group(substr($area->name, 0, 3), 'projects')['total'] ?? 0) >= 1, true);
    $say('a page by its title', collect(SiteSearch::groups('privacy'))->firstWhere('key', 'pages') !== null, true);
    $say('the careers page', collect($group('careers', 'pages')['items'] ?? [])->pluck('url')->contains('/careers'), true);
    $say('title matches rank first', ($group($project->name, 'projects')['items'][0]['name'] ?? null), $project->name);
    $say('nothing for nonsense', SiteSearch::groups('zzqxjwv'), []);

    echo "-- limits and safety\n";
    $say('at most 10 per group', max(array_map(fn (array $g): int => count($g['items']), SiteSearch::groups('gem'))), 10);
    $say('group total counts every match', ($group('gem', 'projects')['total'] ?? 0) >= 10, true);
    $say('boolean operators are inert', SiteSearch::groups('"+-*()<>~@'), []);
    $say('operators beside a word still work', ($group('+'.$area->name.'*', 'projects')['total'] ?? 0) >= 1, true);
    $say('a long query is cut to 100', mb_strlen(SiteSearch::normalise(str_repeat('a ', 200))), 99);

    echo "-- drafts stay out\n";
    $before = $group($project->name, 'projects')['total'];
    $project->forceFill(['publish_status' => PublishStatus::Draft])->saveQuietly();
    $say('a draft project is not found', ($group($project->name, 'projects')['total'] ?? 0), $before - 1);

    echo "-- search_text is kept current\n";
    $fresh = Project::query()->find($project->id);
    $say('project text holds the area name', str_contains((string) $fresh->search_text, $area->name), true);
    $say('project text holds the city name', $area->parent === null || str_contains((string) $fresh->search_text, $area->parent->name), true);
    $area->forceFill(['name' => 'Renamedarea'])->save();
    $say('renaming an area rewrites its projects', str_contains((string) Project::query()->find($project->id)->search_text, 'Renamedarea'), true);
    $say('post text holds the body', str_contains((string) Post::query()->find($post->id)->search_text, substr($post->excerpt, 0, 20)), true);
    $page = Page::query()->published()->whereHas('seoMeta')->first();
    $say('page text is its meta description', $page === null || Page::query()->find($page->id)->search_text === $page->seoMeta->meta_description, true);

    echo "-- HTTP\n";
    $say('/search is 200', $status('/search?q=uttara'), 200);
    $say('one character is 200 with the minimum message', $status('/search?q=a'), 200);
    $codes = [];
    $stamp = random_int(1, 250).'.'.random_int(1, 250);
    for ($i = 0; $i < 62; $i++) {
        $codes[] = $status('/search?q=gem', "10.{$stamp}.7");
    }
    $say('the 61st request in a minute is 429', [$codes[59] ?? null, $codes[60] ?? null], [200, 429]);
} finally {
    DB::rollBack();
}
