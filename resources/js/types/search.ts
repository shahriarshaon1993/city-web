// Project search (spec/search.md, CMP-SEARCH-01). Values are FR-SRCH-002
// query values; `url` is the clean landing page of a single facet
// (HERO-041, FR-SRCH-008), null when the facet has none.

export type SearchOption = {
    value: string;
    label: string;
    url: string | null;
};

/** A Settings → Search size band; `value` is "{min}-{max}" (DEC-42). */
export type SizeBand = {
    value: string;
    label: string;
    min: number | null;
    max: number | null;
};

export type ProjectSearchOptions = {
    /** The listing URL the form submits to (`/projects`). */
    action: string;
    segments: SearchOption[];
    statuses: SearchOption[];
    /** Areas grouped by city (HERO-040). */
    cities: { city: string; areas: SearchOption[] }[];
    sizes: SizeBand[];
    /** 1–5; "5" means 5 or more. */
    beds: SearchOption[];
};

/** The widget's state; `''` = any. */
export type ProjectSearchFilters = {
    segment: string;
    area: string;
    status: string;
    size: string;
    beds: string;
};
