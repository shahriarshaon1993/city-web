// PG-04 props (ProjectDetailResource, DM-PROJ / DM-UNIT / DM-AMEN). Part A
// fills them from App\Support\Demo\DemoProjectDetail (DEC-29).

import type { Breadcrumb, MediaImage, ProjectCard } from '@/types/site';

export type GalleryCategory =
    | 'exterior'
    | 'interior'
    | 'amenity'
    | 'site'
    | 'night';

/** One gallery photo; `image.caption` / `credit` show in the lightbox. */
export type GalleryItem = {
    id: number;
    category: GalleryCategory;
    category_label: string;
    image: MediaImage | null;
};

/** A gallery item that has its image (the page drops the others). */
export type GalleryPhoto = GalleryItem & { image: MediaImage };

export type ProjectHighlight = {
    id: number;
    title: string;
    text: string;
    image: MediaImage | null;
};

/** DM-PROJ "At a glance" columns, raw; the page labels and formats them. */
export type ProjectFacts = {
    address: string | null;
    land_area_katha: number | null;
    storeys: string | null;
    total_units: number | null;
    units_per_floor: number | null;
    lifts: number | null;
    car_parks: number | null;
    basements: number | null;
    road_width_ft: number | null;
    facing: string | null;
    orientation: string | null;
    /** ISO date, month precision. */
    launch_date: string | null;
    handover_date: string | null;
    rajuk_approval_no: string | null;
};

export type AmenityCategory = 'building' | 'security' | 'lifestyle' | 'utility';

export type Amenity = {
    id: number;
    slug: string;
    name: string;
    /** Lucide name from `amenityIcons` (lib/amenity-icons.ts). */
    icon: string;
    category: AmenityCategory;
    category_label: string;
};

export type UnitAvailability = 'available' | 'limited' | 'sold_out';

/** DM-UNIT. `price_bdt` is null unless the project and the unit show prices (AC-PROJ-01/02). */
export type UnitType = {
    id: number;
    name: string;
    size_sft: number;
    size_sft_max: number | null;
    bedrooms: number | null;
    bathrooms: number | null;
    balconies: number | null;
    facing: string | null;
    floor_range: string | null;
    floor_plan: MediaImage | null;
    /** A11Y-D11: room sizes for screen-reader users. */
    plan_description: string | null;
    price_bdt: number | null;
    availability: UnitAvailability;
    availability_label: string;
};

export type ProjectDetail = Omit<ProjectCard, 'location'> & {
    tagline: string | null;
    segment_url: string | null;
    location: { area: string; city: string; url: string };
    /** ISO date, month precision. */
    handover_date: string | null;
    gallery: GalleryItem[];
    /** Sanitised HTML. */
    overview: string | null;
    design_narrative: string | null;
    highlights: ProjectHighlight[];
    facts: ProjectFacts;
    custom_facts: { label: string; value: string }[];
    amenities: Amenity[];
    unit_types: UnitType[];
    breadcrumbs: Breadcrumb[];
};

export type ProjectCopy = {
    overview_heading: string;
    overview_tab: string;
    facts_heading: string;
    facts_tab: string;
    amenities_heading: string;
    amenities_tab: string;
    units_heading: string;
    units_tab: string;
    gallery_heading: string;
    gallery_tab: string;
    price_from_label: string;
};

export type ProjectPageProps = {
    project: ProjectDetail;
    copy: ProjectCopy;
};
