<?php

namespace App\Queries\Site;

use App\Enums\BookingState;
use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Models\Location;
use App\Models\Project;
use App\Support\Catalog\CatalogCache;
use App\Support\Search\ProjectFilters;
use App\Support\Settings\Settings;
use Illuminate\Database\Eloquent\Collection;

/**
 * FR-SRCH-001…007 over the published projects: filters, sorts, disjunctive facet counts (each facet
 * counts with every other filter applied but its own, FR-SRCH-004) and pagination, returning the
 * `projects`, `facets` and `meta` props of PG-02/03 (types/listing.ts).
 *
 * One cached query loads a slim row per published project (a few columns, no relations); filtering,
 * sorting and counting then run in memory, so a listing costs the keyword query, the page's cards
 * and nothing per facet. The keyword (FR-SRCH-011) is the only part MySQL answers: FULLTEXT on name
 * and tagline in natural language mode, `LIKE %q%` on the name for queries under 4 characters, plus
 * projects in an area or city whose name matches. Results are cached 10 minutes under the catalogue
 * version (CMS-CACHE-003). Prices come from `Project::publicPriceFrom()` rules only (AC-PROJ-01).
 *
 * @phpstan-type SlimRow array{id: int, area: string, segment: string, lifecycle: string, booking: string, featured: bool, featured_order: int|null, published_at: string, handover: string|null, size: array{0: int|null, 1: int|null}, beds_max: int|null, price: int|null}
 */
final class ProjectSearch
{
    /** Keyword queries shorter than this fall back to LIKE (FR-SRCH-011). */
    private const int FULLTEXT_MIN = 4;

    private const array SORT_LABELS = [
        'featured' => 'Featured',
        'newest' => 'Newest',
        'handover_asc' => 'Handover: soonest',
        'size_asc' => 'Size: smallest',
        'size_desc' => 'Size: largest',
        'price_asc' => 'Price: lowest',
        'price_desc' => 'Price: highest',
    ];

    /** Price slider step (৳ 5 lakh). */
    private const int PRICE_STEP = 500_000;

    /** Size slider step. */
    private const int SIZE_STEP = 500;

    /**
     * FR-SRCH-005: the price filter and price sorts exist only with enough projects that show a price.
     */
    public static function priceFilterEnabled(): bool
    {
        $visible = count(array_filter(self::slim(), fn (array $row): bool => $row['price'] !== null));

        return $visible >= (int) Settings::group('search')['price_filter_min_projects'];
    }

    /**
     * @return array{projects: list<array<string, mixed>>, facets: array<string, mixed>, meta: array<string, mixed>}
     */
    public function search(ProjectFilters $filters): array
    {
        return CatalogCache::remember('projects:list:'.$this->hash($filters), CatalogCache::LISTING_TTL, function () use ($filters): array {
            $rows = self::slim();
            $keyword = $filters->q === null ? null : $this->keywordIds($filters->q);
            $matching = $this->sorted(array_values(array_filter($rows, fn (array $row): bool => $this->matches($row, $filters, $keyword))), $filters->sort);

            $perPage = (int) Settings::group('search')['results_per_page'];
            $total = count($matching);
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = $filters->page;
            $pageIds = array_column(array_slice($matching, ($page - 1) * $perPage, $perPage), 'id');

            return [
                'projects' => ProjectCards::byIds($pageIds),
                'facets' => $this->facets($rows, $filters, $keyword),
                'meta' => [
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'last_page' => $lastPage,
                    'from' => $total === 0 ? 0 : ($page - 1) * $perPage + 1,
                    'to' => min($total, $page * $perPage),
                    'filters' => [
                        'q' => $filters->q ?? '',
                        'segment' => $filters->segment,
                        'status' => $filters->status,
                        'area' => $filters->areas,
                        'size_min' => $filters->sizeMin,
                        'size_max' => $filters->sizeMax,
                        'beds' => $filters->beds,
                        'price_min' => $filters->priceMin,
                        'price_max' => $filters->priceMax,
                        'booking' => $filters->booking,
                        'featured' => $filters->featured,
                    ],
                    'sort' => $filters->sort,
                    'view' => $filters->view,
                    'active_count' => $filters->activeCount(),
                    'base_url' => ProjectFilters::basePath(),
                    'url' => $filters->url(),
                    'pagination' => $this->pagination($filters, $lastPage),
                ],
            ];
        });
    }

    /**
     * Every result of a state (all pages, in its sort order) as a map point: the card plus its
     * coordinates (FR-SRCH-006, CMP-MAP-01 popups). A project without its own coordinates sits on
     * its area's centre; one with neither is left out.
     *
     * @return list<array<string, mixed>>
     */
    public function mapProjects(ProjectFilters $filters): array
    {
        return CatalogCache::remember('projects:map:'.$this->hash($filters), CatalogCache::LISTING_TTL, function () use ($filters): array {
            $keyword = $filters->q === null ? null : $this->keywordIds($filters->q);
            $matching = $this->sorted(array_values(array_filter(self::slim(), fn (array $row): bool => $this->matches($row, $filters, $keyword))), $filters->sort);
            $ids = array_column($matching, 'id');

            if ($ids === []) {
                return [];
            }

            $projects = ProjectCards::query()->whereIn('id', $ids)->get()->keyBy('id');
            $points = [];

            foreach ($ids as $id) {
                $project = $projects[$id] ?? null;

                if ($project === null) {
                    continue;
                }

                $lat = $project->latitude ?? $project->location->latitude;
                $lng = $project->longitude ?? $project->location->longitude;

                if ($lat !== null && $lng !== null) {
                    $points[] = [...ProjectCards::cards(new Collection([$project]))[0], 'coordinates' => ['lat' => $lat, 'lng' => $lng]];
                }
            }

            return $points;
        });
    }

    /**
     * Areas with projects of one segment/status, most projects first (LAY-PROJ-LIST location links).
     *
     * @return list<array{name: string, city: string, count: int, url: string}>
     */
    public function areaLinks(?string $segment, ?string $status, int $limit = 8): array
    {
        $counts = [];

        foreach (self::slim() as $row) {
            if (($segment === null || $row['segment'] === $segment) && ($status === null || $row['lifecycle'] === $status)) {
                $counts[$row['area']] = ($counts[$row['area']] ?? 0) + 1;
            }
        }

        $links = [];

        foreach (AreaDirectory::all() as $area) {
            if (isset($counts[$area['slug']])) {
                $links[] = ['name' => $area['name'], 'city' => $area['city'], 'count' => $counts[$area['slug']], 'url' => '/locations/'.$area['slug']];
            }
        }

        usort($links, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice($links, 0, $limit);
    }

    /**
     * One slim row per published project, cached under the catalogue version.
     *
     * @return list<SlimRow>
     */
    private static function slim(): array
    {
        return CatalogCache::remember('projects:slim', CatalogCache::LISTING_TTL, function (): array {
            $areas = Location::query()->areas()->pluck('slug', 'id');

            return array_values(Project::query()->published()
                ->get(['id', 'location_id', 'segment', 'lifecycle', 'booking_state', 'is_featured', 'featured_order', 'published_at', 'handover_date', 'size_min_sft', 'size_max_sft', 'beds_max', 'show_price', 'price_from_bdt'])
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'area' => (string) ($areas[$project->location_id] ?? ''),
                    'segment' => $project->segment->value,
                    'lifecycle' => $project->lifecycle->value,
                    'booking' => $project->booking_state->value,
                    'featured' => $project->is_featured,
                    'featured_order' => $project->featured_order,
                    'published_at' => $project->published_at?->format('Y-m-d H:i:s') ?? '0000-00-00 00:00:00',
                    'handover' => $project->handover_date?->format('Y-m'),
                    'size' => [$project->size_min_sft, $project->size_max_sft],
                    'beds_max' => $project->beds_max,
                    'price' => $project->publicPriceFrom(),
                ])->all());
        });
    }

    private function hash(ProjectFilters $filters): string
    {
        return md5($filters->url(false).'|'.json_encode(Settings::group('search')));
    }

    /**
     * FR-SRCH-011: project IDs whose name/tagline match (FULLTEXT, or LIKE under 4 characters) or
     * whose area or city name contains the keyword.
     *
     * @return array<int, true>
     */
    private function keywordIds(string $q): array
    {
        $like = '%'.addcslashes($q, '\\%_').'%';
        $locationIds = Location::query()->where('name', 'like', $like)->pluck('id');
        $locationIds = $locationIds->merge(Location::query()->whereIn('parent_id', $locationIds)->pluck('id'))->all();

        $ids = Project::query()->published()
            ->where(function ($where) use ($q, $like, $locationIds): void {
                if (mb_strlen($q) < self::FULLTEXT_MIN) {
                    $where->where('name', 'like', $like);
                } else {
                    $where->whereFullText(['name', 'tagline'], $q);
                }

                $where->orWhereIn('location_id', $locationIds);
            })
            ->pluck('id');

        return array_fill_keys($ids->all(), true);
    }

    /**
     * @param  SlimRow  $row
     * @param  array<int, true>|null  $keyword  matching IDs, null = no keyword
     * @param  string|null  $except  the facet left out (disjunctive counts)
     */
    private function matches(array $row, ProjectFilters $filters, ?array $keyword, ?string $except = null): bool
    {
        [$sizeMin, $sizeMax] = $row['size'];

        return ($except === 'q' || $keyword === null || isset($keyword[$row['id']]))
            && ($except === 'segment' || $filters->segment === null || $row['segment'] === $filters->segment)
            && ($except === 'status' || $filters->status === null || $row['lifecycle'] === $filters->status)
            && ($except === 'area' || $filters->areas === [] || in_array($row['area'], $filters->areas, true))
            && ($except === 'size' || ($filters->sizeMin === null && $filters->sizeMax === null) || $this->overlaps($sizeMin, $sizeMax, $filters->sizeMin, $filters->sizeMax))
            && ($except === 'beds' || $filters->beds === null || ($row['beds_max'] !== null && $row['beds_max'] >= $filters->beds))
            && ($except === 'price' || ($filters->priceMin === null && $filters->priceMax === null) || $this->matchesPrice($row['price'], $filters->priceMin, $filters->priceMax))
            && ($except === 'booking' || $filters->booking === null || $row['booking'] === $filters->booking)
            && ($except === 'featured' || ! $filters->featured || $row['featured']);
    }

    private function matchesPrice(?int $price, ?int $min, ?int $max): bool
    {
        return $price !== null && ($min === null || $price >= $min) && ($max === null || $price <= $max);
    }

    /** A project's size range overlaps the wanted range; no size never matches. */
    private function overlaps(?int $from, ?int $to, ?int $min, ?int $max): bool
    {
        if ($from === null) {
            return false;
        }

        $to ??= $from;

        return ($min === null || $to >= $min) && ($max === null || $from <= $max);
    }

    /**
     * FR-SRCH-001 sorts; missing values (no size, hidden price) sort last.
     *
     * @param  list<SlimRow>  $rows
     * @return list<SlimRow>
     */
    private function sorted(array $rows, string $sort): array
    {
        $last = PHP_INT_MAX;

        usort($rows, fn (array $a, array $b): int => match ($sort) {
            'newest' => strcmp($b['published_at'], $a['published_at']),
            'handover_asc' => strcmp($a['handover'] ?? '9999', $b['handover'] ?? '9999') ?: strcmp($b['published_at'], $a['published_at']),
            'size_asc' => ($a['size'][0] ?? $last) <=> ($b['size'][0] ?? $last),
            'size_desc' => ($b['size'][1] ?? -1) <=> ($a['size'][1] ?? -1),
            'price_asc' => ($a['price'] ?? $last) <=> ($b['price'] ?? $last),
            'price_desc' => ($b['price'] ?? -1) <=> ($a['price'] ?? -1),
            // `featured`: featured projects in featured order, then newest.
            default => ($a['featured_order'] ?? $last) <=> ($b['featured_order'] ?? $last) ?: strcmp($b['published_at'], $a['published_at']),
        });

        return $rows;
    }

    /**
     * @param  list<SlimRow>  $rows
     * @param  array<int, true>|null  $keyword
     * @return array<string, mixed>
     */
    private function facets(array $rows, ProjectFilters $filters, ?array $keyword): array
    {
        $count = function (string $except, callable $test) use ($rows, $filters, $keyword): int {
            return count(array_filter($rows, fn (array $row): bool => $this->matches($row, $filters, $keyword, $except) && $test($row)));
        };

        $priceEnabled = self::priceFilterEnabled();
        $sizes = array_filter(array_merge(...array_map(fn (array $row): array => $row['size'], $rows)));
        $prices = array_filter(array_column($rows, 'price'));

        $cities = [];

        foreach (AreaDirectory::all() as $area) {
            $cities[$area['city']][] = [
                'value' => $area['slug'],
                'label' => $area['name'],
                'count' => $count('area', fn (array $row): bool => $row['area'] === $area['slug']),
                'url' => ProjectFilters::landingUrl('area', $area['slug']),
            ];
        }

        return [
            'all_count' => $count('segment', fn (): bool => true),
            'segments' => array_map(fn (string $value): array => [
                'value' => $value,
                'label' => Segment::from($value)->label(),
                'count' => $count('segment', fn (array $row): bool => $row['segment'] === $value),
                'url' => ProjectFilters::landingUrl('segment', $value),
            ], ProjectFilters::SEGMENTS),
            'any_status_count' => $count('status', fn (): bool => true),
            'statuses' => array_map(fn (string $value): array => [
                'value' => $value,
                'label' => Lifecycle::from($value)->label(),
                'count' => $count('status', fn (array $row): bool => $row['lifecycle'] === $value),
                'url' => ProjectFilters::landingUrl('status', $value),
            ], ProjectFilters::STATUSES),
            'cities' => array_map(
                fn (string $city, array $areas): array => ['city' => $city, 'areas' => $areas],
                array_keys($cities),
                array_values($cities),
            ),
            'sizes' => array_map(fn (array $band): array => [
                'value' => $band['min'].'-'.$band['max'],
                'label' => $band['label'],
                'min' => $band['min'],
                'max' => $band['max'],
                'count' => $count('size', fn (array $row): bool => $this->overlaps($row['size'][0], $row['size'][1], $band['min'], $band['max'])),
            ], Settings::group('search')['size_bands']),
            'size_bounds' => ['min' => 0, 'max' => (int) (ceil(max($sizes ?: [0]) / self::SIZE_STEP) * self::SIZE_STEP), 'step' => 50],
            'beds' => array_map(fn (int $beds): array => [
                'value' => $beds,
                'label' => $beds === 5 ? '5+' : (string) $beds,
                'count' => $count('beds', fn (array $row): bool => $row['beds_max'] !== null && $row['beds_max'] >= $beds),
            ], range(1, 5)),
            'price_bounds' => $priceEnabled && $prices !== [] ? [
                'min' => (int) (floor(min($prices) / self::PRICE_STEP) * self::PRICE_STEP),
                'max' => (int) (ceil(max($prices) / self::PRICE_STEP) * self::PRICE_STEP),
                'step' => self::PRICE_STEP,
            ] : null,
            'booking' => array_map(fn (string $value): array => [
                'value' => $value,
                'label' => (string) BookingState::from($value)->label(),
                'count' => $count('booking', fn (array $row): bool => $row['booking'] === $value),
            ], ProjectFilters::BOOKING_STATES),
            'featured_count' => $count('featured', fn (array $row): bool => $row['featured']),
            'sorts' => array_values(array_filter(
                array_map(fn (string $value): array => ['value' => $value, 'label' => self::SORT_LABELS[$value]], ProjectFilters::SORTS),
                fn (array $sort): bool => $priceEnabled || ! str_starts_with($sort['value'], 'price_'),
            )),
        ];
    }

    /**
     * CMP-PAGI-01 links: first, last, current ±1, null = ellipsis.
     *
     * @return array{prev: string|null, next: string|null, pages: list<array{page: int, url: string}|null>}
     */
    private function pagination(ProjectFilters $filters, int $lastPage): array
    {
        $page = $filters->page;
        $numbers = array_unique(array_filter(
            [1, $page - 1, $page, $page + 1, $lastPage],
            fn (int $number): bool => $number >= 1 && $number <= $lastPage,
        ));
        sort($numbers);

        $pages = [];
        $previous = 0;

        foreach ($numbers as $number) {
            if ($number - $previous > 1) {
                $pages[] = null;
            }

            $pages[] = ['page' => $number, 'url' => $filters->withPage($number)->url()];
            $previous = $number;
        }

        return [
            'prev' => $page > 1 ? $filters->withPage($page - 1)->url() : null,
            'next' => $page < $lastPage ? $filters->withPage($page + 1)->url() : null,
            'pages' => $lastPage > 1 ? $pages : [],
        ];
    }
}
