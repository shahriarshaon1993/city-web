// PG-04 props (ProjectDetailResource, DM-PROJ / DM-UNIT / DM-AMEN / DM-PROG /
// DM-NEAR / DM-PDOC). Part A fills them from App\Support\Demo\DemoProjectDetail
// and DemoProjectSections (DEC-29).

import type { CtaBandBlock } from '@/types/blocks';
import type {
    Breadcrumb,
    MediaImage,
    ProgressStage,
    ProjectCard,
} from '@/types/site';

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

/** CMP-VIDEO-01: a YouTube video or an uploaded MP4 (captions = WebVTT URL). */
export type ProjectVideo = {
    title: string;
    poster: MediaImage | null;
    duration_seconds: number | null;
} & (
    | { type: 'youtube'; youtube_id: string }
    | {
          type: 'upload';
          mp4: string;
          webm: string | null;
          captions: string | null;
      }
);

/** DM-PROG, newest first; `photos` open in the lightbox. */
export type ProgressUpdate = {
    id: number;
    /** ISO date, month precision. */
    reported_on: string;
    percent: number;
    stage: ProgressStage;
    stage_label: string;
    note: string | null;
    photos: MediaImage[];
};

export type NearbyCategory =
    | 'school'
    | 'hospital'
    | 'mall'
    | 'mosque'
    | 'transport'
    | 'park'
    | 'office'
    | 'airport';

export type NearbyPlace = {
    id: number;
    name: string;
    category: NearbyCategory;
    category_label: string;
    minutes: number;
    mode: 'drive' | 'walk';
    mode_label: string;
};

export type DesignTeamMember = {
    role: 'architect' | 'structural_engineer' | 'contractor';
    role_label: string;
    name: string;
};

/** DM-PDOC. Gated documents have no `url`: a signed URL follows the lead (AC-PROJ-05). */
export type ProjectDocument = {
    id: number;
    title: string;
    kind: 'brochure' | 'price_list' | 'specification' | 'other';
    kind_label: string;
    is_gated: boolean;
    url: string | null;
    bytes: number | null;
    format: string;
};

export type ProjectFaq = {
    id: number;
    question: string;
    /** Sanitised HTML. */
    answer: string;
};

/** CMP-ENQ-01 data; WhatsApp = the project's override, else Settings → Contact (E.164). */
export type ProjectEnquiry = {
    heading: string;
    whatsapp: string | null;
    /** FR-LEAD-013 prefilled text, the project's absolute URL included. */
    whatsapp_text: string;
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
    video: ProjectVideo | null;
    /** Allowed hosts only (my.matterport.com, kuula.co, www.youtube.com). */
    virtual_tour_url: string | null;
    progress: ProgressUpdate[];
    coordinates: { lat: number; lng: number } | null;
    address: string | null;
    nearby: NearbyPlace[];
    design_team: DesignTeamMember[];
    documents: ProjectDocument[];
    faq: ProjectFaq[];
    /** AC-PROJ-07: manual picks, else same segment + city (≤ 3). */
    similar: ProjectCard[];
    enquiry: ProjectEnquiry;
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
    video_heading: string;
    video_tab: string;
    tour_label: string;
    team_heading: string;
    progress_heading: string;
    progress_tab: string;
    progress_timeline_heading: string;
    location_heading: string;
    location_tab: string;
    nearby_heading: string;
    documents_heading: string;
    documents_tab: string;
    faq_heading: string;
    similar_heading: string;
    price_on_request: string;
    sales_name: string;
    /** `{project}` is replaced by the project name. */
    enquire_title: string;
    enquire_text: string;
    visit_title: string;
    visit_text: string;
    brochure_title: string;
    brochure_text: string;
    dialog_alternative: string;
    progress_stages: { value: ProgressStage; label: string }[];
};

export type ProjectPageProps = {
    project: ProjectDetail;
    copy: ProjectCopy;
    cta: CtaBandBlock;
};

/** The three CMP-ENQ-01 dialogs (forms arrive in 7.1). */
export type EnquiryKind = 'enquire' | 'visit' | 'brochure';
