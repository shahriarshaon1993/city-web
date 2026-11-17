<?php

/**
 * Phase 13.3 check: the public pages read the database. Query counts per page (cold and warm cache),
 * hidden prices, search, 410 / 301 rules, slug history, cache busting. Changes are rolled back.
 * Run: php artisan tinker docs/checks/13.3-public-catalog.php < /dev/null   (needs `db:seed` first)
 */

use App\Models\Location;
use App\Models\Project;
use App\Support\Catalog\CatalogCache;
use Illuminate\Http\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$say = function (string $label, mixed $got, mixed $expect): void {
    echo ($got === $expect ? 'ok   ' : 'FAIL ')."{$label}: ".json_encode($got).' (expect '.json_encode($expect).")\n";
};

/** @return array{0: int, 1: int, 2: string, 3: string|null} status, queries, body, Location header */
$get = function (string $uri, array $headers = []): array {
    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });
    $response = app()->handle(Request::create($uri, 'GET', server: $headers));
    app(DeferredCallbackCollection::class)->invoke();

    return [$response->getStatusCode(), $queries, (string) $response->getContent(), $response->headers->get('Location')];
};

// afterCommit listeners never fire inside this rolled-back transaction, so raise the version by hand.
$bump = function (): void {
    Cache::add('cachever:projects', 1);
    Cache::increment('cachever:projects');
    CatalogCache::forgetVersion();
};

Cache::flush();
CatalogCache::forgetVersion();
$pages = ['/', '/projects', '/projects/type/residential', '/projects?q=lake', '/projects/gemcon-lakeview', '/locations', '/locations/uttara', '/locations/uttara/residential', '/shortlist?ids=1,2', '/compare?ids=1,2,3'];

foreach ($pages as $uri) {
    [$status, $cold] = $get($uri);
    [, $warm] = $get($uri);
    echo ($status === 200 && $warm <= 25 ? 'ok   ' : 'FAIL ')."{$uri}: {$status}, queries cold {$cold} / warm {$warm} (expect 200, warm <= 25)\n";
}

// AC-PROJ-01: Orchid Heights hides its prices; they are not in the HTML or the page props.
[, , $body] = $get('/projects/gemcon-orchid-heights');
$say('hidden price absent from the page', str_contains($body, '42000000') || str_contains($body, '"price_bdt":4'), false);
$say('hidden price shows the note', str_contains($body, 'Price on request'), true);
[, , $body] = $get('/projects?sort=price_asc');
$say('hidden price absent from the listing', str_contains($body, '"price_from_bdt":42000000'), false);

// FR-SRCH-011: keyword (FULLTEXT) and short LIKE fallback, facets.
[, , $body] = $get('/projects?q=lakeview');
$say('keyword finds Lakeview', str_contains($body, 'Gemcon Lakeview'), true);
[, , $body] = $get('/projects?q=uttara');
$say('keyword matches an area name', str_contains($body, 'Gemcon Greenfield'), true);
[, , $body] = $get('/projects?q=zzzzqq');
$say('keyword with no match → empty', str_contains($body, '"total":0'), true);
[$status] = $get('/projects?q=ro');
$say('short keyword (LIKE) answers', $status, 200);

// Project page: unknown 404, unpublished 404, deleted 410 / 301, slug history 301.
[$status] = $get('/projects/no-such-project');
$say('unknown slug', $status, 404);

DB::beginTransaction();

$project = Project::query()->where('slug', 'gemcon-lakeview')->firstOrFail();
$project->update(['slug' => 'gemcon-lakeview-new']);
$bump();
[$status, , , $location] = $get('/projects/gemcon-lakeview');
$say('old slug 301s to the new one', [$status, $location], [301, 'http://localhost/projects/gemcon-lakeview-new']);
[$status] = $get('/projects/gemcon-lakeview-new');
$say('new slug is live', $status, 200);

$project->delete();
$bump();
[$status] = $get('/projects/gemcon-lakeview-new');
$say('deleted project → 410', $status, 410);

$project->forceFill(['archived_redirect_to' => '/projects/gemcon-orchid-heights'])->saveQuietly();
[$status, , , $location] = $get('/projects/gemcon-lakeview-new');
$say('deleted with a redirect → 301', [$status, $location], [301, 'http://localhost/projects/gemcon-orchid-heights']);

$project->forceFill(['archived_redirect_to' => null, 'deleted_at' => now()->subDays(91)])->saveQuietly();
[$status] = $get('/projects/gemcon-lakeview-new');
$say('deleted over 90 days ago → 404', $status, 404);

$draft = Project::query()->where('slug', 'gemcon-crescent')->firstOrFail();
$draft->update(['publish_status' => 'draft']);
$bump();
[$status] = $get('/projects/gemcon-crescent');
$say('draft project → 404', $status, 404);
[, , $body] = $get('/projects');
$say('draft project is not listed', str_contains($body, 'Gemcon Crescent'), false);

// Cache busting: a name change shows on the next request without flushing the cache.
$greenfield = Project::query()->where('slug', 'gemcon-greenfield')->firstOrFail();
$get('/projects/gemcon-greenfield');
$greenfield->update(['name' => 'Gemcon Greenfield Renamed']);
$bump();
[, , $body] = $get('/projects/gemcon-greenfield');
$say('renamed project shows at once', str_contains($body, 'Gemcon Greenfield Renamed'), true);

// Areas: slug history, unpublished 404, Purbachal (no projects) still has its page.
$area = Location::query()->where('slug', 'khulshi')->firstOrFail();
$area->update(['slug' => 'khulshi-hills']);
$bump();
[$status, , , $location] = $get('/locations/khulshi');
$say('old area slug 301s', [$status, $location], [301, 'http://localhost/locations/khulshi-hills']);
$area->update(['publish_status' => 'draft']);
$bump();
[$status] = $get('/locations/khulshi-hills');
$say('unpublished area → 404', $status, 404);
[$status] = $get('/locations/purbachal');
$say('area without projects still renders', $status, 200);

DB::rollBack();
Cache::flush();
$say('cache version is a counter', is_int(CatalogCache::version()), true);
