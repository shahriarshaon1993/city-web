// Locations, projects, amenities and facet pages in the admin (ADM-SCR-01…05).
// Shapes of App\Queries\Admin\ProjectEditor, App\Http\Resources\Admin\ProjectRowResource and
// AmenityRowResource, and of the area / facet editors.
import type { MediaAsset } from './media';
import type { PublishingStatus, WorkflowState } from './publishing';

export type CatalogOption = { value: string; label: string };
export type GroupedOption = {
    value: number;
    label: string;
    group?: string;
};

export type SeoForm = {
    meta_title: string;
    meta_description: string;
    canonical_url: string;
    robots: string;
    og_title: string;
    og_description: string;
    og_image_asset_id: number | null;
    twitter_card: string;
    focus_keyword: string;
    schema_enabled: boolean;
};

export type CustomFactForm = { key: string; label: string; value: string };

export type HighlightForm = {
    key: string;
    asset_id: number | null;
    title: string;
    text: string;
};

export type GalleryItemForm = {
    key: string;
    asset_id: number;
    category: string;
    caption: string;
};

export type UnitTypeForm = {
    key: string;
    id?: number;
    name: string;
    size_sft: number | string | null;
    size_sft_max: number | string | null;
    bedrooms: number | string | null;
    bathrooms: number | string | null;
    balconies: number | string | null;
    facing: string;
    floor_range: string;
    floor_plan_asset_id: number | null;
    plan_description: string;
    plan_3d_asset_id: number | null;
    price_bdt: number | string | null;
    show_price: boolean;
    availability: string;
};

export type ProgressForm = {
    key: string;
    id?: number;
    reported_on: string;
    percent: number | string | null;
    stage: string;
    note: string;
    photo_ids: number[];
};

export type NearbyForm = {
    key: string;
    name: string;
    category: string;
    minutes: number | string | null;
    mode: string;
};

export type DocumentForm = {
    key: string;
    asset_id: number | null;
    title: string;
    kind: string;
    is_gated: boolean;
};

/** The project editor's form (the shape SaveProjectRequest validates). */
export type ProjectFormData = {
    name: string;
    slug: string;
    tagline: string;
    segment: string;
    lifecycle: string;
    booking_state: string;
    location_id: number | null;
    is_featured: boolean;

    address: string;
    latitude: number | string | null;
    longitude: number | string | null;
    land_area_katha: number | string | null;
    storeys: string;
    total_units: number | string | null;
    units_per_floor: number | string | null;
    lifts: number | string | null;
    car_parks: number | string | null;
    basements: number | string | null;
    road_width_ft: number | string | null;
    facing: string;
    orientation: string;
    launch_date: string;
    handover_date: string;
    rajuk_approval_no: string;
    architect: string;
    structural_engineer: string;
    contractor: string;
    custom_facts: CustomFactForm[];

    overview: string;
    design_narrative: string;
    highlights: HighlightForm[];

    cover_asset_id: number | null;
    gallery: GalleryItemForm[];
    video_type: string;
    video_url: string;
    video_asset_id: number | null;
    video_poster_asset_id: number | null;
    video_captions_asset_id: number | null;
    virtual_tour_url: string;

    unit_types: UnitTypeForm[];
    amenity_ids: number[];
    progress_updates: ProgressForm[];
    nearby_places: NearbyForm[];
    documents: DocumentForm[];
    brochure_gated: boolean;

    show_price: boolean;
    price_from_manual: boolean;
    price_from_bdt: number | string | null;
    price_note: string;

    default_assignee_id: number | null;
    whatsapp_override: string;
    similar_ids: number[];

    seo: SeoForm;
};

export type AmenityOption = {
    id: number;
    name: string;
    icon: string;
    category: string;
    category_label: string;
};

export type ProjectOptions = {
    segments: CatalogOption[];
    lifecycles: CatalogOption[];
    bookings: CatalogOption[];
    availabilities: CatalogOption[];
    gallery_categories: CatalogOption[];
    progress_stages: CatalogOption[];
    nearby_categories: CatalogOption[];
    travel_modes: CatalogOption[];
    document_kinds: CatalogOption[];
    areas: GroupedOption[];
    amenities: AmenityOption[];
    similar: GroupedOption[];
    assignees: GroupedOption[];
    limits: {
        unit_types: number;
        gallery: number;
        highlights: number;
        custom_facts: number;
        nearby: number;
        documents: number;
        photos: number;
        similar: number;
    };
};

/** CMP-ADM-CHECKLIST item; `tab` and `field` say where to fix it. */
export type ChecklistItem = {
    key: string;
    label: string;
    passed: boolean;
    detail: string | null;
    tab: string;
    field: string;
};

export type ProjectSummary = WorkflowState & {
    name: string;
    has_redirect: boolean;
};

export type ProjectRow = {
    id: number;
    name: string;
    slug: string;
    thumb: string | null;
    segment: { value: string; label: string };
    lifecycle: { value: string; label: string };
    booking: string | null;
    area: string | null;
    city: string | null;
    status: { value: PublishingStatus; label: string };
    /** The published version has changes waiting (CMS-PUB-002). */
    pending: boolean;
    /** ISO time when it (or its pending changes) goes live by itself. */
    scheduled_at: string | null;
    is_featured: boolean;
    updated_at: string | null;
    edit_url: string;
    public_url: string | null;
    can: { edit: boolean; publish: boolean; delete: boolean; create: boolean };
};

export type RedirectOption = { value: string; label: string };

export type AmenityRow = {
    id: number;
    name: string;
    category: { value: string; label: string };
    icon: string;
    projects_count: number;
    projects: Array<{ label: string; href: string }>;
    can: { edit: boolean; delete: boolean };
};

export type AmenityDraft = {
    id?: number;
    name: string;
    category: string;
    icon: string;
};

export type FeaturedRow = {
    id: number;
    name: string;
    slug: string;
    thumb: string | null;
    area: string | null;
    segment: string;
    status: { value: string; label: string };
    edit_url: string;
};

/** DEL-03: what blocks a delete, with links to the usages. */
export type UsageBlock = {
    message: string;
    total: number;
    items: Array<{ label: string; href: string }>;
    list_label: string;
    see_all?: string;
};

export type AreaNode = {
    id: number;
    name: string;
    slug: string;
    status: PublishingStatus;
    status_label: string;
    /** CMS-PUB-002: the published area has changes waiting. */
    pending: boolean;
    scheduled_at: string | null;
    projects_count: number;
    published_projects: number;
    indexable: boolean;
    reasons: string[];
    edit_url: string;
    public_url: string | null;
    usage: UsageBlock | null;
};

export type CityNode = {
    id: number;
    name: string;
    slug: string;
    areas_count: number;
    projects_count: number;
    public_url: string;
    areas: AreaNode[];
    usage: UsageBlock | null;
};

export type FactForm = { key: string; label: string; value: string };

export type AreaFormData = {
    name: string;
    slug: string;
    parent_id: number | null;
    intro: string;
    segment_intros: Record<string, string>;
    facts: FactForm[];
    hero_asset_id: number | null;
    latitude: number | string | null;
    longitude: number | string | null;
    zoom: number | string;
    nearby_ids: number[];
    seo: SeoForm;
};

export type AreaIndexability = {
    indexable: boolean;
    reasons: string[];
    intro_words: number;
    published_projects: number;
};

export type AreaSummary = WorkflowState & {
    name: string;
    projects_count: number;
    indexability: AreaIndexability;
};

export type AreaOptions = {
    cities: GroupedOption[];
    areas: GroupedOption[];
    segments: CatalogOption[];
    min_intro_words: number;
    limits: {
        facts: number;
        nearby: number;
        zoom_min: number;
        zoom_max: number;
    };
};

export type FacetRow = {
    id: number;
    kind: 'segment' | 'status';
    kind_label: string;
    value: string;
    title: string;
    intro_words: number;
    has_seo: boolean;
    path: string;
    public_url: string;
    edit_url: string;
};

export type FacetFormData = { title: string; intro: string; seo: SeoForm };

export type AssetMap = Record<number, MediaAsset>;
