<?php

namespace App\Http\Controllers\Site;

use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Http\Controllers\Controller;
use App\Queries\Site\AreaDirectory;
use App\Queries\Site\FacetPages;
use App\Queries\Site\ProjectCards;
use App\Queries\Site\ProjectSearch;
use App\Support\Demo\DemoContent;
use App\Support\Search\ProjectFilters;
use App\Support\Seo\Breadcrumbs;
use App\Support\Seo\SeoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PG-02 `/projects` and the PG-03 facet landing pages (LAY-PROJ-LIST).
 * Every request is normalised first: a URL that is not its state's one URL
 * gets a 301 (FR-SRCH-002/008, SEO-TEC-022); then robots/canonical follow
 * SEO-TEC-021 and a page beyond the last is a 404 (SEO-TEC-005). Inertia
 * filter visits keep a single area on the listing; only direct requests
 * 301 it to its location page (owner, 5.1).
 */
class ProjectListingController extends Controller
{
    public function __construct(private readonly ProjectSearch $search) {}

    public function index(Request $request): Response|RedirectResponse
    {
        return $this->listing($request, []);
    }

    public function segment(Request $request, string $segment): Response|RedirectResponse
    {
        return $this->listing($request, ['segment' => ProjectFilters::enumValue($segment)]);
    }

    public function status(Request $request, string $status): Response|RedirectResponse
    {
        return $this->listing($request, ['status' => ProjectFilters::enumValue($status)]);
    }

    /**
     * @param  array<string, string>  $pathFacet  the landing route's facet; a query value for the same key wins
     */
    private function listing(Request $request, array $pathFacet): Response|RedirectResponse
    {
        $filters = ProjectFilters::fromQuery($request->query() + $pathFacet, AreaDirectory::slugs());

        if (! ProjectSearch::priceFilterEnabled()) {
            $filters = $filters->withoutPrice();
        }

        if ($request->hasHeader('X-Inertia')) {
            $filters = $filters->withoutAreaLanding();
        }

        if (! $filters->matches($request->path(), $request->server->getString('QUERY_STRING'))) {
            return redirect()->to($filters->url(), 301);
        }

        $results = $this->search->search($filters);

        abort_if($filters->page > $results['meta']['last_page'], 404);

        $header = $this->header($filters);
        $firstCover = $results['projects'][0]['cover'] ?? null;

        $seo = SeoData::page(
            $header['title'].($filters->page > 1 ? " – Page {$filters->page}" : ''),
            $header['description'],
        )->lcp($firstCover, null, '(min-width:1280px) 400px, (min-width:768px) 45vw, 92vw');

        if (! $filters->isIndexable()) {
            $seo->noindex()->canonical(SeoData::absoluteUrl($filters->canonicalPath()));
        }

        return Inertia::render('Site/Projects', [
            ...$results,
            'listing' => [
                'title' => $header['title'],
                'intro' => $header['intro'],
                'faq' => $header['faq'],
                'breadcrumbs' => $header['breadcrumbs'],
                'location_links' => $this->search->areaLinks($filters->segment, $filters->status),
            ],
            // FR-SRCH-006: every result as a marker, only in map view.
            'map' => $filters->view === 'map' ? $this->search->mapProjects($filters) : null,
            'newest' => ProjectCards::newest(),
            'copy' => DemoContent::projectListing()['copy'],
            'cta' => DemoContent::projectListingCta(),
            'seo' => $seo->toArray($request),
        ]);
    }

    /**
     * H1, intro and FAQ: the facet page's own on its landing URL; a filtered
     * view keeps the title of its segment (else status) without intro/FAQ.
     *
     * @return array{title: string, intro: string|null, description: string, faq: list<array<string, mixed>>, breadcrumbs: list<array{label: string, url: string|null}>}
     */
    private function header(ProjectFilters $filters): array
    {
        $copy = DemoContent::projectListing();
        $landing = $filters->landing();
        $listingTitle = 'Projects';

        if ($landing !== null && $landing['kind'] !== 'area') {
            $demo = $copy['facets'][$landing['kind'].':'.$landing['value']];
            $stored = FacetPages::find($landing['kind'], $landing['value']);
            $label = $landing['kind'] === 'segment'
                ? Segment::from($landing['value'])->label()
                : Lifecycle::from($landing['value'])->label();

            return [
                'title' => $stored['title'] ?? $demo['title'],
                'intro' => $stored === null ? $demo['intro'] : $stored['intro'],
                'description' => $stored['meta_description'] ?? $demo['description'],
                'faq' => $demo['faq'],
                'breadcrumbs' => Breadcrumbs::to($label, [[$listingTitle, ProjectFilters::basePath()]]),
            ];
        }

        $nearest = match (true) {
            $filters->segment !== null => $this->facetCopy($copy, 'segment', $filters->segment),
            $filters->status !== null => $this->facetCopy($copy, 'status', $filters->status),
            default => $copy['listing'],
        };

        return [
            'title' => $nearest['title'],
            'intro' => $filters->activeCount() === 0 ? $copy['listing']['intro'] : null,
            'description' => $nearest['description'],
            'faq' => [],
            'breadcrumbs' => Breadcrumbs::to($listingTitle),
        ];
    }

    /**
     * A facet's title and description: the editor's DM-FACET row, else the template copy.
     *
     * @param  array<string, mixed>  $copy
     * @return array{title: string, description: string}
     */
    private function facetCopy(array $copy, string $kind, string $value): array
    {
        $stored = FacetPages::find($kind, $value);
        $demo = $copy['facets']["{$kind}:{$value}"];

        return ['title' => $stored['title'] ?? $demo['title'], 'description' => $stored['meta_description'] ?? $demo['description']];
    }
}
