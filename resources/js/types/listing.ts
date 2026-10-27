// PG-02/PG-03 project listing props (spec/search.md): `projects`, `facets`
// and `meta` are what a filter change reloads (FR-SRCH-003); the rest is
// page chrome. Shapes match App\Support\Demo\DemoProjectSearch, which 13.3's
// Eloquent search keeps.
import type { CtaBandBlock } from '@/types/blocks';
import type { MapProject } from '@/types/location';
import type { Breadcrumb, ProjectCard } from '@/types/site';

export type ListingSort =
    | 'featured'
    | 'newest'
    | 'handover_asc'
    | 'size_asc'
    | 'size_desc'
    | 'price_asc'
    | 'price_desc';

export type ListingView = 'grid' | 'map';

/** FR-SRCH-002 state, normalised by the server; `null`/`''`/`[]` = any. */
export type ListingFilters = {
    q: string;
    segment: string | null;
    status: string | null;
    /** Area slugs, sorted, ≤ 5. */
    area: string[];
    size_min: number | null;
    size_max: number | null;
    /** Minimum bedrooms (1–6). */
    beds: number | null;
    price_min: number | null;
    price_max: number | null;
    booking: string | null;
    featured: boolean;
};

export type ListingState = ListingFilters & {
    sort: ListingSort;
    view: ListingView;
};

/** A facet value with its count (FR-SRCH-004) and landing URL (FR-SRCH-008). */
export type FacetOption = {
    value: string;
    label: string;
    count: number;
    /** Root-relative landing page; null when the value has none (yet). */
    url: string | null;
};

export type CountedOption = {
    value: string;
    label: string;
    count: number;
};

export type SizeBandFacet = CountedOption & {
    min: number | null;
    max: number | null;
};

export type RangeBounds = { min: number; max: number; step: number };

export type ListingFacets = {
    /** Results across all segments (the "All" option). */
    all_count: number;
    segments: FacetOption[];
    any_status_count: number;
    statuses: FacetOption[];
    cities: { city: string; areas: FacetOption[] }[];
    sizes: SizeBandFacet[];
    size_bounds: RangeBounds;
    beds: { value: number; label: string; count: number }[];
    /** Null while the price filter is off (FR-SRCH-005). */
    price_bounds: RangeBounds | null;
    booking: CountedOption[];
    featured_count: number;
    /** Price sorts are absent while the price filter is off. */
    sorts: { value: ListingSort; label: string }[];
};

export type ListingPageLink = { page: number; url: string };

export type ListingMeta = {
    total: number;
    page: number;
    per_page: number;
    last_page: number;
    from: number;
    to: number;
    filters: ListingFilters;
    sort: ListingSort;
    view: ListingView;
    active_count: number;
    /** `/projects`. */
    base_url: string;
    /** The one URL of the current state. */
    url: string;
    /** CMP-PAGI-01; `null` in `pages` = ellipsis. */
    pagination: {
        prev: string | null;
        next: string | null;
        pages: (ListingPageLink | null)[];
    };
};

export type ListingFaq = { id: number; question: string; answer: string };

export type ListingHeader = {
    title: string;
    intro: string | null;
    faq: ListingFaq[];
    breadcrumbs: Breadcrumb[];
    location_links: {
        name: string;
        city: string;
        count: number;
        url: string;
    }[];
};

export type ListingCopy = {
    empty_title: string;
    empty_body: string;
    newest_heading: string;
    locations_heading: string;
    faq_heading: string;
};

export type ProjectListingProps = {
    projects: ProjectCard[];
    facets: ListingFacets;
    meta: ListingMeta;
    listing: ListingHeader;
    /** FR-SRCH-006: every result as a marker; null outside map view. */
    map: MapProject[] | null;
    newest: ProjectCard[];
    copy: ListingCopy;
    cta: CtaBandBlock;
};
