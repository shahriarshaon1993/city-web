<?php

namespace App\Support\Search;

use Illuminate\Support\Facades\Route;

/**
 * The normalised project-listing state (FR-SRCH-001/002 + "Validation" and
 * "Edge cases" of spec/search.md). Built from a raw query: unknown params
 * dropped, numbers cast and clamped, min > max swapped, ≤ 5 areas, `q`
 * stripped of tags and cut to 80 chars, invalid enum values ignored. `url()`
 * is the one URL of the state: a single segment/status/area goes to its
 * landing page (FR-SRCH-008), other params are alphabetical with empty and
 * default values omitted. Permanent code: 13.3 keeps it with Eloquent.
 *
 * @phpstan-type Pair array{0: string, 1: string}
 */
final class ProjectFilters
{
    public const array SEGMENTS = ['residential', 'commercial', 'mixed_use', 'land'];

    public const array STATUSES = ['upcoming', 'ongoing', 'ready', 'handed_over'];

    /** Booking states a visitor can filter by (`none` is not one). */
    public const array BOOKING_STATES = ['booking_open', 'limited_units', 'sold_out'];

    public const array SORTS = ['featured', 'newest', 'handover_asc', 'size_asc', 'size_desc', 'price_asc', 'price_desc'];

    public const string DEFAULT_SORT = 'featured';

    public const int SIZE_MAX = 20_000;

    public const int BEDS_MIN = 1;

    public const int BEDS_MAX = 6;

    /** ৳ 1,000 crore: anything above is a typo, not a budget. */
    public const int PRICE_MAX = 10_000_000_000;

    public const int AREAS_MAX = 5;

    public const int Q_MAX = 80;

    /** Kept through every redirect and never a filter (7.2 reads them on landing). */
    private const string TRACKING_PATTERN = '/^(utm_[a-z]+|gclid|fbclid|msclkid)$/';

    /**
     * @param  list<string>  $areas  area slugs, sorted
     * @param  list<Pair>  $tracking  campaign params passed through untouched
     */
    private function __construct(
        public readonly ?string $q,
        public readonly ?string $segment,
        public readonly ?string $status,
        public readonly array $areas,
        public readonly ?int $sizeMin,
        public readonly ?int $sizeMax,
        public readonly ?int $beds,
        public readonly ?int $priceMin,
        public readonly ?int $priceMax,
        public readonly ?string $booking,
        public readonly bool $featured,
        public readonly string $sort,
        public readonly string $view,
        public readonly int $page,
        private readonly array $tracking,
    ) {}

    /**
     * @param  array<string, mixed>  $query  `$request->query()`; a landing route merges its facet in first
     * @param  list<string>  $areaSlugs  the published area slugs (FR-LOC)
     */
    public static function fromQuery(array $query, array $areaSlugs): self
    {
        [$sizeMin, $sizeMax] = self::sizeRange($query);
        [$sizeMin, $sizeMax] = self::ordered($sizeMin, $sizeMax);
        [$priceMin, $priceMax] = self::ordered(
            self::integer($query['price_min'] ?? null, 0, self::PRICE_MAX),
            self::integer($query['price_max'] ?? null, 0, self::PRICE_MAX),
        );

        $areas = is_array($query['area'] ?? null) ? $query['area'] : [$query['area'] ?? null];
        $areas = array_values(array_intersect(
            array_unique(array_filter(array_map(self::slug(...), $areas))),
            $areaSlugs,
        ));
        sort($areas);

        $tracking = [];

        foreach ($query as $key => $value) {
            if (preg_match(self::TRACKING_PATTERN, (string) $key) === 1 && is_string($value) && $value !== '') {
                $tracking[] = [(string) $key, $value];
            }
        }

        $sort = self::enum($query['sort'] ?? null, self::SORTS) ?? self::DEFAULT_SORT;

        return new self(
            q: self::keyword($query['q'] ?? null),
            segment: self::enum($query['segment'] ?? null, self::SEGMENTS),
            status: self::enum($query['status'] ?? null, self::STATUSES),
            areas: array_slice($areas, 0, self::AREAS_MAX),
            sizeMin: $sizeMin,
            sizeMax: $sizeMax,
            beds: self::integer($query['beds'] ?? null, self::BEDS_MIN, self::BEDS_MAX),
            priceMin: $priceMin,
            priceMax: $priceMax,
            booking: self::enum($query['booking'] ?? null, self::BOOKING_STATES),
            featured: in_array($query['featured'] ?? null, ['1', 'true', 'on'], true),
            sort: $sort,
            view: ($query['view'] ?? null) === 'map' ? 'map' : 'grid',
            page: self::integer($query['page'] ?? null, 1, PHP_INT_MAX) ?? 1,
            tracking: $tracking,
        );
    }

    /**
     * Same state without price filters or price sorts, for when the price
     * filter is hidden (FR-SRCH-005).
     */
    public function withoutPrice(): self
    {
        return $this->with(priceMin: null, priceMax: null, sort: str_starts_with($this->sort, 'price_') ? self::DEFAULT_SORT : $this->sort);
    }

    public function withPage(int $page): self
    {
        return $this->with(page: $page);
    }

    /** Number of active filters (sort, view and page are not filters). */
    public function activeCount(): int
    {
        return count(array_filter([
            $this->q, $this->segment, $this->status, $this->sizeMin, $this->sizeMax, $this->beds,
            $this->priceMin, $this->priceMax, $this->booking, $this->featured ?: null,
        ], fn (mixed $value): bool => $value !== null)) + count($this->areas);
    }

    /**
     * The landing page this state is, when its only filter is one segment,
     * status or area (FR-SRCH-008); sort, view and page may still be set.
     *
     * @return array{kind: 'segment'|'status'|'area', value: string, url: string}|null
     */
    public function landing(): ?array
    {
        if ($this->activeCount() !== 1) {
            return null;
        }

        $facet = match (true) {
            $this->segment !== null => ['segment', $this->segment],
            $this->status !== null => ['status', $this->status],
            count($this->areas) === 1 => ['area', $this->areas[0]],
            default => null,
        };

        $url = $facet === null ? null : self::landingUrl($facet[0], $facet[1]);

        return $url === null ? null : ['kind' => $facet[0], 'value' => $facet[1], 'url' => $url];
    }

    /**
     * SEO-TEC-020/021: only `/projects` and the landing pages without sort or
     * map view (pages 2…n included) are indexable.
     */
    public function isIndexable(): bool
    {
        return ($this->activeCount() === 0 || $this->landing() !== null)
            && $this->sort === self::DEFAULT_SORT
            && $this->view === 'grid';
    }

    /**
     * SEO-TEC-021 "closest indexable page" (root-relative): a single segment's
     * type page, else a single area's location page, else a single status's
     * status page, else `/projects`. Page 1 always.
     */
    public function canonicalPath(): string
    {
        if ($this->isIndexable()) {
            return $this->url(withTracking: false);
        }

        $candidates = [
            ['segment', $this->segment],
            ['area', count($this->areas) === 1 ? $this->areas[0] : null],
            ['status', $this->status],
        ];

        foreach ($candidates as [$kind, $value]) {
            $url = $value === null ? null : self::landingUrl($kind, $value);

            if ($url !== null) {
                return $url;
            }
        }

        return self::basePath();
    }

    /**
     * The one URL of this state (root-relative).
     */
    public function url(bool $withTracking = true): string
    {
        $landing = $this->landing();
        $pairs = $this->pairs(skipFilters: $landing !== null);

        if ($withTracking) {
            $pairs = [...$pairs, ...$this->tracking];
            usort($pairs, fn (array $a, array $b): int => strcmp($a[0], $b[0]));
        }

        $query = implode('&', array_map(
            fn (array $pair): string => $pair[0].'='.rawurlencode($pair[1]),
            $pairs,
        ));

        return ($landing['url'] ?? self::basePath()).($query === '' ? '' : '?'.$query);
    }

    /**
     * Whether the request's path and query already are `url()`; compared
     * decoded, so `area%5B%5D` and `area[]` or `+` and `%20` are equal.
     */
    public function matches(string $path, string $rawQuery): bool
    {
        $target = $this->url();
        $targetPath = strtok($target, '?') ?: $target;
        $targetQuery = (string) parse_url($target, PHP_URL_QUERY);

        return '/'.ltrim($path, '/') === $targetPath
            && self::decodedPairs($rawQuery) === self::decodedPairs($targetQuery);
    }

    /**
     * The landing URL of one facet value, or null when it has no page (yet):
     * location pages arrive in 5.1, so single areas stay on the listing until
     * the route exists.
     */
    public static function landingUrl(string $kind, string $value): ?string
    {
        return match ($kind) {
            'segment' => route('site.projects.segment', self::urlSlug($value), false),
            'status' => route('site.projects.status', self::urlSlug($value), false),
            'area' => Route::has('site.locations.show') ? route('site.locations.show', $value, false) : null,
            default => null,
        };
    }

    /** Enum value → URL slug (`mixed_use` → `mixed-use`, owner in 4.1). */
    public static function urlSlug(string $value): string
    {
        return str_replace('_', '-', $value);
    }

    /** URL slug or enum value → enum value. */
    public static function enumValue(string $slug): string
    {
        return str_replace('-', '_', strtolower($slug));
    }

    public static function basePath(): string
    {
        return route('site.projects.index', absolute: false);
    }

    /**
     * Query pairs in key order; `skipFilters` leaves out the facet that the
     * landing path already carries.
     *
     * @return list<Pair>
     */
    private function pairs(bool $skipFilters): array
    {
        $pairs = [];

        if (! $skipFilters) {
            foreach ($this->areas as $area) {
                $pairs[] = ['area[]', $area];
            }

            $pairs = [...$pairs, ...array_filter([
                ['beds', $this->beds],
                ['booking', $this->booking],
                ['featured', $this->featured ? '1' : null],
                ['price_max', $this->priceMax],
                ['price_min', $this->priceMin],
                ['q', $this->q],
                ['segment', $this->segment],
                ['size_max', $this->sizeMax],
                ['size_min', $this->sizeMin],
                ['status', $this->status],
            ], fn (array $pair): bool => $pair[1] !== null)];
        }

        if ($this->page > 1) {
            $pairs[] = ['page', $this->page];
        }

        if ($this->sort !== self::DEFAULT_SORT) {
            $pairs[] = ['sort', $this->sort];
        }

        if ($this->view === 'map') {
            $pairs[] = ['view', 'map'];
        }

        $pairs = array_map(fn (array $pair): array => [$pair[0], (string) $pair[1]], $pairs);
        usort($pairs, fn (array $a, array $b): int => strcmp($a[0], $b[0]));

        return $pairs;
    }

    private function with(mixed ...$changes): self
    {
        $values = [
            'q' => $this->q, 'segment' => $this->segment, 'status' => $this->status, 'areas' => $this->areas,
            'sizeMin' => $this->sizeMin, 'sizeMax' => $this->sizeMax, 'beds' => $this->beds,
            'priceMin' => $this->priceMin, 'priceMax' => $this->priceMax, 'booking' => $this->booking,
            'featured' => $this->featured, 'sort' => $this->sort, 'view' => $this->view, 'page' => $this->page,
            'tracking' => $this->tracking,
        ];

        return new self(...array_merge($values, $changes));
    }

    /**
     * `size_min`/`size_max`, or the no-JS search widget's `size={min}-{max}` (DEC-42).
     *
     * @param  array<string, mixed>  $query
     * @return array{0: int|null, 1: int|null}
     */
    private static function sizeRange(array $query): array
    {
        $min = self::integer($query['size_min'] ?? null, 0, self::SIZE_MAX);
        $max = self::integer($query['size_max'] ?? null, 0, self::SIZE_MAX);

        if ($min === null && $max === null && is_string($query['size'] ?? null) && str_contains($query['size'], '-')) {
            [$bandMin, $bandMax] = explode('-', $query['size'], 2);
            $min = self::integer($bandMin, 0, self::SIZE_MAX);
            $max = self::integer($bandMax, 0, self::SIZE_MAX);
        }

        return [$min === 0 ? null : $min, $max];
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private static function ordered(?int $min, ?int $max): array
    {
        return $min !== null && $max !== null && $min > $max ? [$max, $min] : [$min, $max];
    }

    private static function integer(mixed $value, int $min, int $max): ?int
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        if (! is_string($value) || preg_match('/^\d{1,15}$/', trim($value)) !== 1) {
            return null;
        }

        return max($min, min($max, (int) trim($value)));
    }

    /**
     * @param  list<string>  $allowed
     */
    private static function enum(mixed $value, array $allowed): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = self::enumValue(trim($value));

        return in_array($value, $allowed, true) ? $value : null;
    }

    private static function slug(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? strtolower(trim($value)) : null;
    }

    private static function keyword(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim((string) preg_replace('/\s+/u', ' ', strip_tags($value)));
        $value = trim(mb_substr($value, 0, self::Q_MAX));

        return $value === '' ? null : $value;
    }

    /**
     * @return list<Pair>
     */
    private static function decodedPairs(string $rawQuery): array
    {
        $pairs = [];

        foreach (explode('&', $rawQuery) as $part) {
            if ($part === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $part, 2), 2, '');
            $pairs[] = [urldecode($key), urldecode($value)];
        }

        return $pairs;
    }
}
