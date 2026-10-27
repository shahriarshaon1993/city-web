<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Demo\DemoContent;
use App\Support\Demo\DemoLocations;
use App\Support\Demo\DemoMedia;
use App\Support\Demo\DemoProjects;
use App\Support\Demo\DemoProjectSearch;
use App\Support\Search\ProjectFilters;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\LocationMeta;
use App\Support\Seo\ProjectMeta;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-05 `/locations` (LAY-LOC-LIST) and PG-06 `/locations/{area}` and
 * `/locations/{area}/{segment}` (LAY-LOC-DETAIL). Area pages are listing
 * landing pages (FR-SRCH-008): they accept only `page`; any other filter,
 * sort or view 301s to its `/projects?…` URL. Indexability follows
 * FR-LOC-003/004. 13.3 swaps the demo classes for the Location model and
 * the Eloquent search (same shapes).
 */
class LocationController extends Controller
{
    public function __construct(private readonly DemoProjectSearch $search) {}

    public function index(Request $request): Response
    {
        $copy = DemoLocations::copy();
        $cities = [];

        foreach (DemoProjects::areas() as $area) {
            $image = DemoLocations::detail($area['slug'])['image'] ?? null;

            $cities[$area['city']][] = [
                'slug' => $area['slug'],
                'name' => $area['name'],
                'url' => route('site.locations.show', $area['slug'], false),
                'count' => $area['count'],
                'image' => $image === null ? null : DemoMedia::image($image),
            ];
        }

        // FR-LOC-005: cities and their areas alphabetical.
        ksort($cities);
        $directory = [];

        foreach ($cities as $city => $areas) {
            usort($areas, fn (array $a, array $b): int => strcmp($a['name'], $b['name']));
            $directory[] = ['name' => $city, 'project_count' => array_sum(array_column($areas, 'count')), 'areas' => $areas];
        }

        $firstImage = $directory[0]['areas'][0]['image'] ?? null;

        $seo = SeoData::page($copy['directory_title'], $copy['directory_description'])
            ->lcp($firstImage, null, '(min-width:1280px) 256px, (min-width:1024px) 21vw, (min-width:640px) 31vw, 46vw');

        return Inertia::render('Site/Locations', [
            'directory' => [
                'title' => $copy['directory_title'],
                'intro' => $copy['directory_intro'],
                'breadcrumbs' => Breadcrumbs::to($copy['directory_title']),
                'cities' => $directory,
                'no_projects' => $copy['no_projects'],
            ],
            'cta' => DemoContent::projectListingCta(),
            'seo' => $seo->toArray($request),
        ]);
    }

    public function show(Request $request, string $area): Response|RedirectResponse
    {
        abort_if(DemoLocations::detail($area) === null, 404);

        $filters = $this->filters($request, ['area' => $area]);

        if (! $filters->matches($request->path(), $request->server->getString('QUERY_STRING'))) {
            return redirect()->to($filters->url(), 301);
        }

        return $this->page($request, $area, null, $filters);
    }

    public function segment(Request $request, string $area, string $segment): Response|RedirectResponse
    {
        abort_if(DemoLocations::detail($area) === null, 404);

        // FR-LOC-004: the page exists only with ≥ 2 published projects in the segment.
        $segment = ProjectFilters::enumValue($segment);
        abort_if((DemoProjectSearch::segmentCounts($area)[$segment] ?? 0) < 2, 404);

        $filters = $this->filters($request, ['area' => $area, 'segment' => $segment]);

        $isLanding = $filters->areas === [$area] && $filters->segment === $segment && $filters->activeCount() === 2
            && $filters->sort === ProjectFilters::DEFAULT_SORT && $filters->view === 'grid';

        if (! $isLanding) {
            return redirect()->to($filters->url(), 301);
        }

        $filters = $filters->onLanding(route('site.locations.segment', [$area, ProjectFilters::urlSlug($segment)], false));

        if (! $filters->matches($request->path(), $request->server->getString('QUERY_STRING'))) {
            return redirect()->to($filters->url(), 301);
        }

        return $this->page($request, $area, $segment, $filters);
    }

    /**
     * @param  array<string, string>  $pathFacets  the route's facets; a query value for the same key wins
     */
    private function filters(Request $request, array $pathFacets): ProjectFilters
    {
        $filters = ProjectFilters::fromQuery($request->query() + $pathFacets, DemoProjectSearch::areaSlugs());

        return DemoProjectSearch::priceFilterEnabled() ? $filters : $filters->withoutPrice();
    }

    private function page(Request $request, string $slug, ?string $segment, ProjectFilters $filters): Response
    {
        $results = $this->search->search($filters);

        abort_if($filters->page > $results['meta']['last_page'], 404);

        $area = DemoProjects::area($slug);
        $detail = DemoLocations::detail($slug) ?? abort(404);
        $centre = DemoLocations::centre($slug);
        $counts = DemoProjectSearch::segmentCounts($slug);
        $total = array_sum($counts);
        $areaUrl = route('site.locations.show', $slug, false);
        $areaIntro = DemoLocations::introHtml($slug);
        $fill = fn (string $text, array $extra = []): string => strtr($text, ['{area}' => $area['name'], '{city}' => $area['city'], ...$extra]);
        $copy = DemoLocations::copy();
        $noun = null;

        if ($segment === null) {
            $title = $fill($copy['area_title']);
            $intro = $areaIntro;
            $indexable = LocationMeta::areaIndexable($total, $areaIntro);
            $breadcrumbs = Breadcrumbs::to($area['name'], [[$copy['directory_title'], route('site.locations.index', absolute: false)]]);
        } else {
            // FR-LOC-004: without its own intro, a template sentence + the area intro, noindex.
            $noun = ProjectMeta::SEGMENT_NOUNS[$segment];
            $own = $detail['segments'][$segment] ?? null;
            $title = $fill($copy['segment_title'], ['{segment}' => $noun]);
            $intro = $own ?? $fill($copy['segment_template'], [
                '{count}' => (string) $counts[$segment],
                '{segment_lower}' => strtolower(DemoProjects::SEGMENT_LABELS[$segment]),
            ]).$areaIntro;
            $indexable = $own !== null;
            $breadcrumbs = Breadcrumbs::to(DemoProjects::SEGMENT_LABELS[$segment], [
                [$copy['directory_title'], route('site.locations.index', absolute: false)],
                [$area['name'], $areaUrl],
            ]);
        }

        $hero = DemoMedia::image($detail['image']);

        $seo = SeoData::raw(LocationMeta::title($area['name'], $area['city'], $noun), LocationMeta::description($intro))
            ->image(DemoMedia::ogImage($detail['image']), $hero['alt'] ?? null)
            ->lcp($hero, null, '100vw');

        if (! $indexable) {
            $seo->noindex();
        }

        return Inertia::render('Site/Location', [
            ...$results,
            'area' => [
                'slug' => $slug,
                'name' => $area['name'],
                'city' => $area['city'],
                'url' => $areaUrl,
                'title' => $title,
                'intro' => $intro,
                'hero' => $hero,
                'facts' => array_map(fn (array $fact): array => ['label' => $fact[0], 'value' => $fact[1]], $detail['facts']),
                'faq' => array_map(
                    fn (array $item, int $index): array => ['id' => $index + 1, 'question' => $item[0], 'answer' => $item[1]],
                    $detail['faq'],
                    array_keys($detail['faq']),
                ),
                'project_count' => $total,
                'segment' => $segment,
                'segments' => $this->segmentLinks($slug, $counts),
                'nearby' => $this->nearby($detail['nearby']),
                'breadcrumbs' => $breadcrumbs,
            ],
            'map' => [
                'projects' => $this->search->mapProjects($filters),
                'centre' => ['lat' => $centre['lat'], 'lng' => $centre['lng']],
                'zoom' => $centre['zoom'],
            ],
            'newest' => $total === 0 ? $this->search->newest() : [],
            'copy' => array_map($fill, array_intersect_key($copy, array_flip([
                'facts_heading', 'projects_heading', 'map_heading', 'nearby_heading', 'faq_heading',
                'empty_title', 'empty_body', 'newest_heading',
            ]))),
            'cta' => DemoContent::projectListingCta(),
            'starts_with_hero' => true,
            'seo' => $seo->toArray($request),
        ]);
    }

    /**
     * The area's segments with projects; `url` only where the area × segment
     * page exists (FR-LOC-004), so the others are not links.
     *
     * @param  array<string, int>  $counts
     * @return list<array{value: string, label: string, count: int, url: string|null}>
     */
    private function segmentLinks(string $slug, array $counts): array
    {
        $links = [];

        foreach (ProjectFilters::SEGMENTS as $segment) {
            $count = $counts[$segment] ?? 0;

            if ($count > 0) {
                $links[] = [
                    'value' => $segment,
                    'label' => DemoProjects::SEGMENT_LABELS[$segment],
                    'count' => $count,
                    'url' => $count >= 2 ? route('site.locations.segment', [$slug, ProjectFilters::urlSlug($segment)], false) : null,
                ];
            }
        }

        return $links;
    }

    /**
     * Nearby areas (manual, ≤ 6) as LocationLinks rows (SEO-ONP-005).
     *
     * @param  list<string>  $slugs
     * @return list<array{name: string, city: string, count: int, url: string}>
     */
    private function nearby(array $slugs): array
    {
        $areas = array_column(DemoProjects::areas(), null, 'slug');

        return array_map(fn (string $slug): array => [
            'name' => $areas[$slug]['name'],
            'city' => $areas[$slug]['city'],
            'count' => $areas[$slug]['count'],
            'url' => route('site.locations.show', $slug, false),
        ], array_slice($slugs, 0, 6));
    }
}
