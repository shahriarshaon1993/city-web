<?php

namespace App\Http\Controllers\Site;

use App\Enums\Segment;
use App\Http\Controllers\Controller;
use App\Queries\Site\AreaDirectory;
use App\Queries\Site\AreaPage;
use App\Queries\Site\ProjectCards;
use App\Queries\Site\ProjectSearch;
use App\Support\Catalog\CatalogCopy;
use App\Support\Search\ProjectFilters;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\LocationMeta;
use App\Support\Seo\ProjectMeta;
use App\Support\Seo\SeoData;
use App\Support\Seo\SeoOverrides;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-05 `/locations` (LAY-LOC-LIST) and PG-06 `/locations/{area}` and
 * `/locations/{area}/{segment}` (LAY-LOC-DETAIL). Area pages are listing
 * landing pages (FR-SRCH-008): they accept only `page`; any other filter,
 * sort or view 301s to its `/projects?…` URL. Indexability follows
 * FR-LOC-003/004. An area that used to have another slug 301s to the current
 * one (SEO-TEC-003); an unpublished or unknown area is a 404.
 */
class LocationController extends Controller
{
    public function __construct(private readonly ProjectSearch $search) {}

    public function index(Request $request): Response
    {
        $copy = CatalogCopy::locations();
        $images = AreaDirectory::images();
        $cities = [];

        foreach (AreaDirectory::all() as $area) {
            $cities[$area['city']][] = [
                'slug' => $area['slug'],
                'name' => $area['name'],
                'url' => route('site.locations.show', $area['slug'], false),
                'count' => $area['count'],
                'image' => $images[$area['slug']] ?? null,
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
            'cta' => CatalogCopy::listingCta(),
            'seo' => $seo->toArray($request),
        ]);
    }

    public function show(Request $request, string $area): Response|RedirectResponse
    {
        $page = AreaPage::find($area);

        if ($page === null) {
            return $this->missing($area);
        }

        $filters = $this->filters($request, ['area' => $area]);

        if (! $filters->matches($request->path(), $request->server->getString('QUERY_STRING'))) {
            return redirect()->to($filters->url(), 301);
        }

        return $this->page($request, $page, null, $filters);
    }

    public function segment(Request $request, string $area, string $segment): Response|RedirectResponse
    {
        $page = AreaPage::find($area);

        if ($page === null) {
            return $this->missing($area);
        }

        // FR-LOC-004: the page exists only with ≥ 2 published projects in the segment.
        $segment = ProjectFilters::enumValue($segment);
        abort_if((AreaDirectory::segmentCounts($page['id'])[$segment] ?? 0) < 2, 404);

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

        return $this->page($request, $page, $segment, $filters);
    }

    /**
     * CMS-PUB-005: the area page of an unsaved copy (a pending revision), `noindex,nofollow`. The
     * projects listed are those of the area's saved slug, since projects know the area by its row.
     *
     * @param  array{id: int, area: array<string, mixed>, intro: string, segment_intros: array<string, string>, centre: array{lat: float, lng: float, zoom: int}, seo: array<string, mixed>}  $page
     */
    public function preview(Request $request, array $page, string $savedSlug): Response
    {
        $filters = ProjectFilters::fromQuery(['area' => $savedSlug], [...AreaDirectory::slugs(), $savedSlug]);

        return $this->page($request, $page, null, ProjectSearch::priceFilterEnabled() ? $filters : $filters->withoutPrice(), true);
    }

    private function missing(string $slug): RedirectResponse
    {
        $current = AreaPage::movedTo($slug);

        abort_if($current === null, 404);

        return redirect()->to(route('site.locations.show', $current, false), 301);
    }

    /**
     * @param  array<string, string>  $pathFacets  the route's facets; a query value for the same key wins
     */
    private function filters(Request $request, array $pathFacets): ProjectFilters
    {
        $filters = ProjectFilters::fromQuery($request->query() + $pathFacets, AreaDirectory::slugs());

        return ProjectSearch::priceFilterEnabled() ? $filters : $filters->withoutPrice();
    }

    /**
     * @param  array{id: int, area: array<string, mixed>, intro: string, segment_intros: array<string, string>, centre: array{lat: float, lng: float, zoom: int}, seo: array<string, mixed>}  $page
     */
    private function page(Request $request, array $page, ?string $segment, ProjectFilters $filters, bool $preview = false): Response
    {
        $results = $this->search->search($filters);

        abort_if($filters->page > $results['meta']['last_page'], 404);

        $area = $page['area'];
        $slug = $area['slug'];
        $counts = AreaDirectory::segmentCounts($page['id']);
        $total = array_sum($counts);
        $areaUrl = route('site.locations.show', $slug, false);
        $areaIntro = $page['intro'];
        $fill = fn (string $text, array $extra = []): string => strtr($text, ['{area}' => $area['name'], '{city}' => $area['city'], ...$extra]);
        $copy = CatalogCopy::locations();
        $noun = null;

        if ($segment === null) {
            $title = $fill($copy['area_title']);
            $intro = $areaIntro;
            $indexable = LocationMeta::areaIndexable($total, $areaIntro);
            $breadcrumbs = Breadcrumbs::to($area['name'], [[$copy['directory_title'], route('site.locations.index', absolute: false)]]);
        } else {
            // FR-LOC-004: without its own intro, a template sentence + the area intro, noindex.
            $noun = ProjectMeta::SEGMENT_NOUNS[$segment];
            $own = $page['segment_intros'][$segment] ?? null;
            $title = $fill($copy['segment_title'], ['{segment}' => $noun]);
            $intro = $own ?? $fill($copy['segment_template'], [
                '{count}' => (string) $counts[$segment],
                '{segment_lower}' => strtolower(Segment::from($segment)->label()),
            ]).$areaIntro;
            $indexable = $own !== null;
            $breadcrumbs = Breadcrumbs::to(Segment::from($segment)->label(), [
                [$copy['directory_title'], route('site.locations.index', absolute: false)],
                [$area['name'], $areaUrl],
            ]);
        }

        $hero = $area['hero'];

        // The editor's SEO panel belongs to the area page; an area × segment page keeps the templates.
        $stored = $segment === null ? $page['seo'] : ['og_image' => $page['seo']['og_image'] ?? null];
        $seo = SeoOverrides::apply($stored, LocationMeta::title($area['name'], $area['city'], $noun), LocationMeta::description($intro), null, $hero['alt'] ?? null)
            ->lcp($hero, null, '100vw');

        if (! $indexable) {
            $seo->noindex();
        }

        if ($preview) {
            $seo->robots(SeoData::ROBOTS_NONE);
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
                'facts' => $area['facts'],
                'faq' => $area['faq'],
                'project_count' => $total,
                'segment' => $segment,
                'segments' => $this->segmentLinks($slug, $counts),
                'nearby' => $area['nearby'],
                'breadcrumbs' => $breadcrumbs,
            ],
            'map' => [
                'projects' => $this->search->mapProjects($filters),
                'centre' => ['lat' => $page['centre']['lat'], 'lng' => $page['centre']['lng']],
                'zoom' => $page['centre']['zoom'],
            ],
            'newest' => $total === 0 ? ProjectCards::newest() : [],
            'copy' => array_map($fill, array_intersect_key($copy, array_flip([
                'facts_heading', 'projects_heading', 'map_heading', 'nearby_heading', 'faq_heading',
                'empty_title', 'empty_body', 'newest_heading',
            ]))),
            'cta' => CatalogCopy::listingCta(),
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
                    'label' => Segment::from($segment)->label(),
                    'count' => $count,
                    'url' => $count >= 2 ? route('site.locations.segment', [$slug, ProjectFilters::urlSlug($segment)], false) : null,
                ];
            }
        }

        return $links;
    }
}
