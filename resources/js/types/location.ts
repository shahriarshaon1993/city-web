// PG-05 locations directory and PG-06 area pages (spec/locations.md). Area
// pages carry the listing's `projects`/`facets`/`meta` for one area (and
// segment) so pagination reuses the listing composable. Shapes match
// App\Http\Controllers\Site\LocationController; 13.3 keeps them.
import type { CtaBandBlock } from '@/types/blocks';
import type {
    ListingFacets,
    ListingFaq,
    ListingHeader,
    ListingMeta,
} from '@/types/listing';
import type { Breadcrumb, MediaImage, ProjectCard } from '@/types/site';

export type LatLng = { lat: number; lng: number };

/** A map marker: the card (CMP-MAP-01 popup) plus its position. */
export type MapProject = ProjectCard & { coordinates: LatLng };

export type DirectoryArea = {
    slug: string;
    name: string;
    url: string;
    count: number;
    image: MediaImage | null;
};

export type LocationsPageProps = {
    directory: {
        title: string;
        intro: string;
        breadcrumbs: Breadcrumb[];
        /** FR-LOC-005: alphabetical, areas alphabetical within. */
        cities: {
            name: string;
            project_count: number;
            areas: DirectoryArea[];
        }[];
        no_projects: string;
    };
    cta: CtaBandBlock;
};

/** An area's segment with projects; `url` only where FR-LOC-004 gives it a page. */
export type AreaSegmentLink = {
    value: string;
    label: string;
    count: number;
    url: string | null;
};

export type AreaDetail = {
    slug: string;
    name: string;
    city: string;
    url: string;
    /** H1 (area or area × segment). */
    title: string;
    /** Sanitised rich text. */
    intro: string;
    hero: MediaImage | null;
    facts: { label: string; value: string }[];
    faq: ListingFaq[];
    project_count: number;
    /** Set on `/locations/{area}/{segment}`. */
    segment: string | null;
    segments: AreaSegmentLink[];
    nearby: ListingHeader['location_links'];
    breadcrumbs: Breadcrumb[];
};

export type AreaMap = {
    projects: MapProject[];
    centre: LatLng;
    zoom: number;
};

export type LocationCopy = {
    facts_heading: string;
    projects_heading: string;
    map_heading: string;
    nearby_heading: string;
    faq_heading: string;
    empty_title: string;
    empty_body: string;
    newest_heading: string;
};

export type LocationPageProps = {
    area: AreaDetail;
    projects: ProjectCard[];
    facets: ListingFacets;
    meta: ListingMeta;
    map: AreaMap;
    newest: ProjectCard[];
    copy: LocationCopy;
    cta: CtaBandBlock;
};
