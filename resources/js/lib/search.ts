import type {
    ProjectSearchFilters,
    ProjectSearchOptions,
    SearchOption,
} from '@/types/search';

export function emptySearchFilters(): ProjectSearchFilters {
    return { segment: '', area: '', status: '', size: '', beds: '' };
}

function facetOptions(
    key: keyof ProjectSearchFilters,
    options: ProjectSearchOptions,
): SearchOption[] {
    switch (key) {
        case 'segment':
            return options.segments;
        case 'status':
            return options.statuses;
        case 'area':
            return options.cities.flatMap((city) => city.areas);
        case 'beds':
            return options.beds;
        case 'size':
            return options.sizes.map(({ value, label }) => ({
                value,
                label,
                url: null,
            }));
    }
}

const FACET_ORDER: (keyof ProjectSearchFilters)[] = [
    'segment',
    'area',
    'status',
    'size',
    'beds',
];

function activeFacets(
    filters: ProjectSearchFilters,
): (keyof ProjectSearchFilters)[] {
    return FACET_ORDER.filter((key) => filters[key] !== '');
}

/**
 * HERO-041: one facet with a landing page → its clean URL; otherwise the
 * listing with FR-SRCH-002 params (empty omitted, alphabetical, size band
 * split into `size_min`/`size_max`).
 */
export function projectSearchUrl(
    filters: ProjectSearchFilters,
    options: ProjectSearchOptions,
): string {
    const active = activeFacets(filters);

    if (active.length === 1) {
        const key = active[0];
        const landing = facetOptions(key, options).find(
            (option) => option.value === filters[key],
        )?.url;

        if (landing) {
            return landing;
        }
    }

    const params: [string, string][] = [];

    if (filters.area) {
        params.push(['area[]', filters.area]);
    }

    if (filters.beds) {
        params.push(['beds', filters.beds]);
    }

    if (filters.segment) {
        params.push(['segment', filters.segment]);
    }

    const band = options.sizes.find((size) => size.value === filters.size);

    if (band?.max != null) {
        params.push(['size_max', String(band.max)]);
    }

    if (band?.min != null) {
        params.push(['size_min', String(band.min)]);
    }

    if (filters.status) {
        params.push(['status', filters.status]);
    }

    params.sort(([a], [b]) => a.localeCompare(b));

    const query = new URLSearchParams(params).toString();

    return query ? `${options.action}?${query}` : options.action;
}

/** Labels of the chosen facets, for the compact trigger (CMP-SEARCH-01). */
export function projectSearchSummary(
    filters: ProjectSearchFilters,
    options: ProjectSearchOptions,
): string[] {
    return activeFacets(filters).flatMap((key) => {
        const label = facetOptions(key, options).find(
            (option) => option.value === filters[key],
        )?.label;

        return label ? [label] : [];
    });
}
