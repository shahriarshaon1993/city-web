<?php

namespace App\Support\Demo;

use App\Support\Search\ProjectFilters;

/**
 * FR-SRCH-001…007 over the demo rows: filters, sorts, facet counts and
 * pagination, returning the `projects`, `facets` and `meta` props of PG-02/03
 * (types/listing.ts). 13.3's Eloquent search returns exactly this shape.
 * Facet counts are disjunctive: each facet counts with every other filter
 * applied but its own (FR-SRCH-004).
 *
 * @phpstan-import-type DemoProject from DemoProjects
 */
final class DemoProjectSearch
{
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
     * FR-SRCH-005: price filter and price sorts only with enough visible prices.
     */
    public static function priceFilterEnabled(): bool
    {
        $visible = count(array_filter(DemoProjects::rows(), fn (array $row): bool => $row['price_visible'] && $row['price'] !== null));

        return $visible >= (int) DemoContent::settings('search')['price_filter_min_projects'];
    }

    /**
     * @return list<string>
     */
    public static function areaSlugs(): array
    {
        return array_column(DemoProjects::areas(), 'slug');
    }

    /**
     * @return array{projects: list<array<string, mixed>>, facets: array<string, mixed>, meta: array<string, mixed>}
     */
    public function search(ProjectFilters $filters): array
    {
        $rows = DemoProjects::rows();
        $matching = array_values(array_filter($rows, fn (array $row): bool => $this->matches($row, $filters)));
        $matching = $this->sorted($matching, $filters->sort);

        $perPage = (int) DemoContent::settings('search')['results_per_page'];
        $total = count($matching);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = $filters->page;
        $pageRows = array_slice($matching, ($page - 1) * $perPage, $perPage);

        return [
            'projects' => array_map(DemoProjects::card(...), $pageRows),
            'facets' => $this->facets($rows, $filters),
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
    }

    /**
     * The 3 newest projects (AC-SRCH-04 empty state).
     *
     * @return list<array<string, mixed>>
     */
    public function newest(int $limit = 3): array
    {
        return array_map(DemoProjects::card(...), array_slice($this->sorted(DemoProjects::rows(), 'newest'), 0, $limit));
    }

    /**
     * Every result of a state (all pages, in its sort order) as a map point:
     * the card plus its coordinates (FR-SRCH-006, CMP-MAP-01 popups).
     *
     * @return list<array<string, mixed>>
     */
    public function mapProjects(ProjectFilters $filters): array
    {
        $rows = array_values(array_filter(DemoProjects::rows(), fn (array $row): bool => $this->matches($row, $filters)));

        return array_map(
            fn (array $row): array => [...DemoProjects::card($row), 'coordinates' => DemoProjectSections::coordinates($row)],
            $this->sorted($rows, $filters->sort),
        );
    }

    /**
     * Published projects per segment in one area (FR-LOC-004).
     *
     * @return array<string, int>
     */
    public static function segmentCounts(string $area): array
    {
        $rows = array_filter(DemoProjects::rows(), fn (array $row): bool => $row['area'] === $area);

        return array_count_values(array_column($rows, 'segment'));
    }

    /**
     * Areas with projects of one segment/status, most projects first (LAY-PROJ-LIST location links).
     *
     * @return list<array{name: string, city: string, count: int, url: string}>
     */
    public function areaLinks(?string $segment, ?string $status, int $limit = 8): array
    {
        $counts = [];

        foreach (DemoProjects::rows() as $row) {
            if (($segment === null || $row['segment'] === $segment) && ($status === null || $row['lifecycle'] === $status)) {
                $counts[$row['area']] = ($counts[$row['area']] ?? 0) + 1;
            }
        }

        $links = [];

        foreach (DemoProjects::areas() as $area) {
            if (isset($counts[$area['slug']])) {
                $links[] = ['name' => $area['name'], 'city' => $area['city'], 'count' => $counts[$area['slug']], 'url' => '/locations/'.$area['slug']];
            }
        }

        usort($links, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice($links, 0, $limit);
    }

    /**
     * @param  DemoProject  $row
     * @param  string|null  $except  the facet left out (disjunctive counts)
     */
    private function matches(array $row, ProjectFilters $filters, ?string $except = null): bool
    {
        [$sizeMin, $sizeMax] = $row['size'];

        return ($except === 'q' || $filters->q === null || $this->matchesKeyword($row, $filters->q))
            && ($except === 'segment' || $filters->segment === null || $row['segment'] === $filters->segment)
            && ($except === 'status' || $filters->status === null || $row['lifecycle'] === $filters->status)
            && ($except === 'area' || $filters->areas === [] || in_array($row['area'], $filters->areas, true))
            && ($except === 'size' || ($filters->sizeMin === null && $filters->sizeMax === null) || $this->overlaps($sizeMin, $sizeMax, $filters->sizeMin, $filters->sizeMax))
            && ($except === 'beds' || $filters->beds === null || ($row['beds'][1] !== null && $row['beds'][1] >= $filters->beds))
            && ($except === 'price' || ($filters->priceMin === null && $filters->priceMax === null) || $this->matchesPrice($row, $filters->priceMin, $filters->priceMax))
            && ($except === 'booking' || $filters->booking === null || $row['booking_state'] === $filters->booking)
            && ($except === 'featured' || ! $filters->featured || $row['featured']);
    }

    /**
     * FR-SRCH-011 stand-in: every word appears in the name, tagline or location.
     *
     * @param  DemoProject  $row
     */
    private function matchesKeyword(array $row, string $q): bool
    {
        $area = DemoProjects::area($row['area']);
        $haystack = mb_strtolower($row['name'].' '.$row['tagline'].' '.$area['name'].' '.$area['city']);

        foreach (preg_split('/\s+/', mb_strtolower($q)) ?: [] as $word) {
            if ($word !== '' && ! str_contains($haystack, $word)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  DemoProject  $row
     */
    private function matchesPrice(array $row, ?int $min, ?int $max): bool
    {
        $price = $row['price_visible'] ? $row['price'] : null;

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
     * @param  list<DemoProject>  $rows
     * @return list<DemoProject>
     */
    private function sorted(array $rows, string $sort): array
    {
        $last = PHP_INT_MAX;
        $price = fn (array $row): ?int => $row['price_visible'] ? $row['price'] : null;

        usort($rows, fn (array $a, array $b): int => match ($sort) {
            'newest' => strcmp($b['published_at'], $a['published_at']),
            'handover_asc' => strcmp($a['handover'] ?? '9999', $b['handover'] ?? '9999') ?: strcmp($b['published_at'], $a['published_at']),
            'size_asc' => ($a['size'][0] ?? $last) <=> ($b['size'][0] ?? $last),
            'size_desc' => ($b['size'][1] ?? -1) <=> ($a['size'][1] ?? -1),
            'price_asc' => ($price($a) ?? $last) <=> ($price($b) ?? $last),
            'price_desc' => ($price($b) ?? -1) <=> ($price($a) ?? -1),
            // `featured`: featured projects in featured order, then newest.
            default => ($a['featured_order'] ?? $last) <=> ($b['featured_order'] ?? $last) ?: strcmp($b['published_at'], $a['published_at']),
        });

        return $rows;
    }

    /**
     * @param  list<DemoProject>  $rows
     * @return array<string, mixed>
     */
    private function facets(array $rows, ProjectFilters $filters): array
    {
        $count = function (string $except, callable $test) use ($rows, $filters): int {
            return count(array_filter($rows, fn (array $row): bool => $this->matches($row, $filters, $except) && $test($row)));
        };

        $priceEnabled = self::priceFilterEnabled();
        $sizes = array_filter(array_merge(...array_map(fn (array $row): array => $row['size'], $rows)));
        $prices = array_filter(array_map(fn (array $row): ?int => $row['price_visible'] ? $row['price'] : null, $rows));

        $cities = [];

        foreach (DemoProjects::areas() as $area) {
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
                'label' => DemoProjects::SEGMENT_LABELS[$value],
                'count' => $count('segment', fn (array $row): bool => $row['segment'] === $value),
                'url' => ProjectFilters::landingUrl('segment', $value),
            ], ProjectFilters::SEGMENTS),
            'any_status_count' => $count('status', fn (): bool => true),
            'statuses' => array_map(fn (string $value): array => [
                'value' => $value,
                'label' => DemoProjects::LIFECYCLE_LABELS[$value],
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
            ], DemoContent::settings('search')['size_bands']),
            'size_bounds' => ['min' => 0, 'max' => (int) (ceil(max($sizes ?: [0]) / self::SIZE_STEP) * self::SIZE_STEP), 'step' => 50],
            'beds' => array_map(fn (int $beds): array => [
                'value' => $beds,
                'label' => $beds === 5 ? '5+' : (string) $beds,
                'count' => $count('beds', fn (array $row): bool => $row['beds'][1] !== null && $row['beds'][1] >= $beds),
            ], range(1, 5)),
            'price_bounds' => $priceEnabled && $prices !== [] ? [
                'min' => (int) (floor(min($prices) / self::PRICE_STEP) * self::PRICE_STEP),
                'max' => (int) (ceil(max($prices) / self::PRICE_STEP) * self::PRICE_STEP),
                'step' => self::PRICE_STEP,
            ] : null,
            'booking' => array_map(fn (string $value): array => [
                'value' => $value,
                'label' => DemoProjects::BOOKING_LABELS[$value],
                'count' => $count('booking', fn (array $row): bool => $row['booking_state'] === $value),
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
