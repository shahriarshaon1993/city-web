import { formatBdtCompact, formatSft } from '@/lib/format';
import type {
    ListingFacets,
    ListingFilters,
    ListingMeta,
    ListingState,
} from '@/types/listing';

// Client twin of App\Support\Search\ProjectFilters::url(): one URL per
// state, so filter visits never hit the server's 301 (FR-SRCH-002/008).
// Change both together. Page is always reset (a filter change starts at 1).

export function emptyFilters(): ListingFilters {
    return {
        q: '',
        segment: null,
        status: null,
        area: [],
        size_min: null,
        size_max: null,
        beds: null,
        price_min: null,
        price_max: null,
        booking: null,
        featured: false,
    };
}

export function stateFromMeta(meta: ListingMeta): ListingState {
    return {
        ...meta.filters,
        area: [...meta.filters.area],
        sort: meta.sort,
        view: meta.view,
    };
}

export function activeCount(state: ListingFilters): number {
    return (
        [
            state.q.trim() || null,
            state.segment,
            state.status,
            state.size_min,
            state.size_max,
            state.beds,
            state.price_min,
            state.price_max,
            state.booking,
            state.featured || null,
        ].filter((value) => value !== null).length + state.area.length
    );
}

/** The landing page of a single segment, status or area (null when none). */
function landingUrl(state: ListingState, facets: ListingFacets): string | null {
    if (activeCount(state) !== 1) {
        return null;
    }

    if (state.segment) {
        return (
            facets.segments.find((option) => option.value === state.segment)
                ?.url ?? null
        );
    }

    if (state.status) {
        return (
            facets.statuses.find((option) => option.value === state.status)
                ?.url ?? null
        );
    }

    if (state.area.length === 1) {
        return (
            facets.cities
                .flatMap((city) => city.areas)
                .find((option) => option.value === state.area[0])?.url ?? null
        );
    }

    return null;
}

export function listingUrl(
    state: ListingState,
    facets: ListingFacets,
    baseUrl: string,
): string {
    const landing = landingUrl(state, facets);
    const pairs: [string, string][] = [];
    const add = (key: string, value: string | number | null): void => {
        if (value !== null && value !== '') {
            pairs.push([key, String(value)]);
        }
    };

    if (landing === null) {
        [...state.area].sort().forEach((area) => add('area[]', area));
        add('beds', state.beds);
        add('booking', state.booking);
        add('featured', state.featured ? '1' : null);
        add('price_max', state.price_max);
        add('price_min', state.price_min);
        add('q', state.q.trim().replace(/\s+/g, ' ').slice(0, 80).trim());
        add('segment', state.segment);
        add('size_max', state.size_max);
        add('size_min', state.size_min);
        add('status', state.status);
    }

    add('sort', state.sort === 'featured' ? null : state.sort);
    add('view', state.view === 'map' ? 'map' : null);

    // Stable sort keeps the area[] values in order (FR-SRCH-002).
    pairs.sort(([a], [b]) => (a < b ? -1 : a > b ? 1 : 0));

    const query = pairs
        .map(([key, value]) => `${key}=${encodeURIComponent(value)}`)
        .join('&');

    return (landing ?? baseUrl) + (query ? `?${query}` : '');
}

export type FilterChip = {
    key: string;
    label: string;
    remove: Partial<ListingState>;
};

function rangeLabel(
    min: number | null,
    max: number | null,
    format: (value: number) => string,
): string | null {
    if (min === null && max === null) {
        return null;
    }

    if (max === null) {
        return `${format(min as number)}+`;
    }

    if (min === null) {
        return `Up to ${format(max)}`;
    }

    return `${format(min)} – ${format(max)}`;
}

export function sizeLabel(state: ListingFilters): string | null {
    return rangeLabel(state.size_min, state.size_max, formatSft);
}

export function priceLabel(state: ListingFilters): string | null {
    return rangeLabel(state.price_min, state.price_max, formatBdtCompact);
}

/** CMP-FILT-01 active filter chips ("Remove filter: Uttara"), in bar order. */
export function activeChips(
    state: ListingFilters,
    facets: ListingFacets,
): FilterChip[] {
    const chips: FilterChip[] = [];
    const label = (
        options: { value: string; label: string }[],
        value: string,
    ): string =>
        options.find((option) => option.value === value)?.label ?? value;

    if (state.q.trim()) {
        chips.push({
            key: 'q',
            label: `“${state.q.trim()}”`,
            remove: { q: '' },
        });
    }

    if (state.segment) {
        chips.push({
            key: 'segment',
            label: label(facets.segments, state.segment),
            remove: { segment: null },
        });
    }

    if (state.status) {
        chips.push({
            key: 'status',
            label: label(facets.statuses, state.status),
            remove: { status: null },
        });
    }

    const areas = facets.cities.flatMap((city) => city.areas);

    for (const area of state.area) {
        chips.push({
            key: `area:${area}`,
            label: label(areas, area),
            remove: { area: state.area.filter((value) => value !== area) },
        });
    }

    const size = sizeLabel(state);

    if (size) {
        chips.push({
            key: 'size',
            label: size,
            remove: { size_min: null, size_max: null },
        });
    }

    if (state.beds !== null) {
        chips.push({
            key: 'beds',
            label: `${state.beds}+ beds`,
            remove: { beds: null },
        });
    }

    const price = priceLabel(state);

    if (price) {
        chips.push({
            key: 'price',
            label: price,
            remove: { price_min: null, price_max: null },
        });
    }

    if (state.booking) {
        chips.push({
            key: 'booking',
            label: label(facets.booking, state.booking),
            remove: { booking: null },
        });
    }

    if (state.featured) {
        chips.push({
            key: 'featured',
            label: 'Featured',
            remove: { featured: false },
        });
    }

    return chips;
}

/** SEO-TEC-023: a URL without a query is an indexable page → `<a href>`. */
export function isCrawlable(url: string): boolean {
    return !url.includes('?');
}
