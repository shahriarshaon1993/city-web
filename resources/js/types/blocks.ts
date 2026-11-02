// Page-builder blocks (CMS-PB-001, DM-BLOCK), shaped like the Part B block
// resources (ARC-002): editor-only fields (manual IDs, preset filters, limit)
// are resolved server-side by the block data loaders and not sent.

import type { DirectoryArea } from '@/types/location';
import type { ProjectVideo } from '@/types/project';
import type { ProjectSearchOptions } from '@/types/search';
import type {
    Breadcrumb,
    MediaImage,
    MediaVideo,
    NewsCard,
    Office,
    Partner,
    ProgressStage,
    ProgressSummary,
    ProjectCard,
    ProjectSegment,
    Testimonial,
} from '@/types/site';

export type BlockBackground =
    | 'default'
    | 'muted'
    | 'brand'
    | 'dark'
    | 'gradient';

export type BlockSpacing = 'none' | 'sm' | 'md' | 'lg';

export type Block<Type extends string, Data> = {
    id: number;
    type: Type;
    /** Hidden blocks are normally dropped server-side; previews may send them. */
    is_visible: boolean;
    anchor_id: string | null;
    background: BlockBackground;
    spacing: BlockSpacing;
    animate: boolean;
    data: Data;
};

/** A CTA (label ≤ 24 chars); `url` is resolved server-side. */
export type BlockLink = {
    label: string;
    url: string;
    new_tab: boolean;
};

export type HeroSlide = {
    image: MediaImage | null;
    /** Portrait crop below 768 px (HERO-013). */
    mobile_image: MediaImage | null;
};

/** spec/hero.md §6.2–6.4. */
export type HeroBlockData = {
    mode: 'single' | 'slider';
    /** `single` only (HERO-002). */
    media_type: 'image' | 'video';
    /** The image, or the video poster (HERO-021). */
    image: MediaImage | null;
    mobile_image: MediaImage | null;
    video: MediaVideo | null;
    /** `slider` only: 2–5 images (HERO-003). */
    slides: HeroSlide[];
    /** `slider` only; off by default (HERO-003). */
    autoplay: boolean;
    eyebrow: string | null;
    headline: string;
    subheadline: string | null;
    primary_cta: BlockLink | null;
    secondary_cta: BlockLink | null;
    /** 0–80; at least 35 while a headline is present. */
    overlay_opacity: number;
    overlay_style: 'solid' | 'gradient-bottom' | 'gradient-left';
    text_align: 'left' | 'center';
    show_search: boolean;
    height: 'full' | 'large' | 'medium';
    /** Present when `show_search` (block data loader). */
    search: ProjectSearchOptions | null;
};

export type HeroBlock = Block<'hero', HeroBlockData>;

/** Block 3, CMP-BLK-INTRO. */
export type IntroStatementBlockData = {
    eyebrow: string | null;
    heading: string;
    /** Sanitised rich text (≤ 120 words). */
    body: string | null;
    cta: BlockLink | null;
    image: MediaImage | null;
};

/** Block 4, CMP-BLK-FEATURED. */
export type FeaturedProjectsBlockData = {
    heading: string;
    source: 'featured' | 'manual' | 'segment' | 'status';
    layout: 'carousel' | 'grid';
    projects: ProjectCard[];
    /** Listing for the source (all, segment or status); null for `manual`. */
    view_all: BlockLink | null;
};

/** Block 5, CMP-BLK-GRID. */
export type ProjectGridBlockData = {
    heading: string | null;
    projects: ProjectCard[];
    /** Present when "show View all" is on: the preset's listing URL. */
    view_all: BlockLink | null;
};

export type SegmentTile = {
    segment: ProjectSegment;
    /** Defaults to the segment label. */
    label: string;
    description: string | null;
    image: MediaImage | null;
    /** The segment's facet page (FR-SRCH-008). */
    url: string;
};

/** Block 6, CMP-BLK-SEGMENTS: 2–4 tiles. */
export type SegmentTilesBlockData = {
    heading: string | null;
    tiles: SegmentTile[];
};

export type StatItem = {
    value: number;
    /** ≤ 4 chars, e.g. "+", "M sft". */
    suffix: string | null;
    label: string;
    decimals: 0 | 1;
};

/** Block 7, CMP-BLK-STATS (CMP-STAT-01): 2–6 items. */
export type StatsCountersBlockData = {
    heading: string | null;
    items: StatItem[];
};

/** Block 8, CMP-BLK-PROGRESS: 1–3 CMP-PROG-01 summaries. */
export type ProgressHighlightBlockData = {
    heading: string;
    /** The four DM-PROG stages in order, for the segmented bar. */
    stages: { value: ProgressStage; label: string }[];
    items: ProgressSummary[];
};

/** Block 9, CMP-BLK-TESTIMONIALS; the source and limit are resolved server-side. */
export type TestimonialsBlockData = {
    heading: string | null;
    layout: 'carousel' | 'grid';
    items: Testimonial[];
};

/** Block 10, CMP-BLK-MARQUEE (MOT-MARQUEE: slow 60 s, normal 40 s per loop). */
export type PartnersMarqueeBlockData = {
    heading: string | null;
    speed: 'slow' | 'normal';
    direction: 'left' | 'right';
    partners: Partner[];
};

/** Block 11, CMP-BLK-NEWS: 3 or 6 newest posts. */
export type LatestNewsBlockData = {
    heading: string | null;
    posts: NewsCard[];
    /** The news index, or the category archive when filtered. */
    view_all: BlockLink | null;
};

/** Block 12, CMP-BLK-CTA (CMP-CTA-01). */
export type CtaBandBlockData = {
    /** ≤ 70 chars. */
    heading: string;
    text: string | null;
    primary_cta: BlockLink;
    secondary_cta: BlockLink | null;
    style: 'brand' | 'gradient' | 'image';
    /** `image` style only. */
    image: MediaImage | null;
};

/** Block 2, CMP-BLK-PAGEHEAD. `breadcrumbs` is null when the block's toggle is off. */
export type PageHeaderBlockData = {
    /** ≤ 80 chars. */
    title: string;
    intro: string | null;
    /** A photo turns the header into a hero under the transparent header. */
    image: MediaImage | null;
    breadcrumbs: Breadcrumb[] | null;
};

/** Block 13, CMP-BLK-RICH. */
export type RichTextBlockData = {
    /** Sanitised rich text: p, h2, h3, ul, ol, blockquote, a, strong, em, hr, table. */
    body: string;
    /** `narrow` 68ch, `wide` two columns from 1024 px. */
    width: 'narrow' | 'wide';
};

/** Block 14, CMP-BLK-IMGTXT. */
export type ImageTextBlockData = {
    heading: string;
    /** Sanitised rich text. */
    body: string;
    image: MediaImage;
    image_side: 'left' | 'right';
    cta: BlockLink | null;
    reveal: 'mask' | 'none';
};

export type FeatureItem = {
    /** Lucide name from the allowlist (lib/feature-icons.ts); null when `image` is used. */
    icon: string | null;
    image: MediaImage | null;
    title: string;
    text: string | null;
    link: BlockLink | null;
};

/** Block 15, CMP-BLK-FEATURES: 2–12 items. */
export type FeatureCardsBlockData = {
    heading: string | null;
    columns: 2 | 3 | 4;
    tilt: boolean;
    items: FeatureItem[];
};

export type ProcessStep = {
    title: string;
    text: string;
};

/** Block 16, CMP-BLK-STEPS: 2–8 steps. */
export type ProcessStepsBlockData = {
    heading: string;
    steps: ProcessStep[];
};

export type TimelineItem = {
    /** 1950–2100. */
    year: number;
    title: string;
    text: string | null;
    image: MediaImage | null;
};

/** Block 17, CMP-BLK-TIMELINE: 2–30 items, oldest first. */
export type TimelineBlockData = {
    heading: string;
    items: TimelineItem[];
};

export type Pillar = {
    label: string;
    title: string;
    text: string;
    image: MediaImage | null;
};

/** Block 18, CMP-BLK-PILLARS: 2–5 tabs. */
export type TabbedPillarsBlockData = {
    heading: string | null;
    tabs: Pillar[];
};

/** Block 27, CMP-BLK-QUOTE. */
export type QuoteBlockData = {
    /** ≤ 400 chars. */
    quote: string;
    person: string;
    role: string | null;
    photo: MediaImage | null;
};

/** Block 28, CMP-BLK-PARALLAX. */
export type ParallaxBandBlockData = {
    image: MediaImage;
    heading: string | null;
    text: string | null;
    /** `md` 60vh, `lg` 80vh. */
    height: 'md' | 'lg';
    parallax: boolean;
};

export type TeamMember = {
    id: number;
    name: string;
    /** Role title. */
    role: string;
    /** Portrait 4:5. */
    photo: MediaImage | null;
    /** ≤ 400 chars; shown when present. */
    bio: string | null;
    linkedin_url: string | null;
    /** Only sent when the member's `show_email` is on (FR-SOC-004). */
    email: string | null;
};

/** Block 19, CMP-BLK-TEAM: the members of one group, in order (FR-SOC-004). */
export type TeamGridBlockData = {
    heading: string | null;
    group: 'leadership' | 'management' | 'team';
    members: TeamMember[];
};

export type FaqItem = {
    id: number;
    question: string;
    /** Sanitised rich text: p, strong, a, ul, ol. */
    answer: string;
};

/** Block 20, CMP-BLK-FAQ; the category or manual picks are resolved server-side. */
export type FaqBlockData = {
    heading: string | null;
    items: FaqItem[];
};

/** Block 21, CMP-BLK-GALLERY: 2–40 images, alt text required. */
export type GalleryBlockData = {
    heading: string | null;
    layout: 'grid' | 'masonry';
    images: MediaImage[];
};

/** Block 22, CMP-BLK-VIDEO (CMP-VIDEO-01). */
export type VideoBlockData = {
    heading: string | null;
    video: ProjectVideo;
    caption: string | null;
};

export type AwardItem = {
    image: MediaImage;
    title: string;
    issuer: string | null;
    year: number | null;
};

/** Block 23, CMP-BLK-AWARDS: 1–24 items. */
export type AwardsBlockData = {
    heading: string | null;
    items: AwardItem[];
};

/** Block 24, CMP-BLK-LOCATIONS; the city filter and limit are resolved server-side. */
export type LocationsGridBlockData = {
    heading: string | null;
    areas: DirectoryArea[];
    /** Label for a project-less area ("No projects yet"). */
    no_projects: string;
    view_all: BlockLink | null;
};

/** Block 25, CMP-BLK-OFFICES; empty office IDs resolve to all offices. */
export type OfficesBlockData = {
    heading: string | null;
    offices: Office[];
};

/** Block 26, CMP-BLK-FORM. The form itself arrives with 7.1; the shell links to its page. */
export type FormBlockData = {
    heading: string;
    intro: string | null;
    form_type: 'contact' | 'callback' | 'landowner' | 'nrb';
    /** Sanitised rich text beside the form. */
    side: string | null;
    /** Where the form lives until 7.1 mounts it in the panel. */
    fallback: BlockLink;
};

export type IntroStatementBlock = Block<
    'intro_statement',
    IntroStatementBlockData
>;
export type FeaturedProjectsBlock = Block<
    'featured_projects',
    FeaturedProjectsBlockData
>;
export type ProjectGridBlock = Block<'project_grid', ProjectGridBlockData>;
export type SegmentTilesBlock = Block<'segment_tiles', SegmentTilesBlockData>;
export type StatsCountersBlock = Block<
    'stats_counters',
    StatsCountersBlockData
>;

export type ProgressHighlightBlock = Block<
    'progress_highlight',
    ProgressHighlightBlockData
>;
export type TestimonialsBlock = Block<'testimonials', TestimonialsBlockData>;
export type PartnersMarqueeBlock = Block<
    'partners_marquee',
    PartnersMarqueeBlockData
>;
export type LatestNewsBlock = Block<'latest_news', LatestNewsBlockData>;
export type CtaBandBlock = Block<'cta_band', CtaBandBlockData>;

export type PageHeaderBlock = Block<'page_header', PageHeaderBlockData>;
export type RichTextBlock = Block<'rich_text', RichTextBlockData>;
export type ImageTextBlock = Block<'image_text', ImageTextBlockData>;
export type FeatureCardsBlock = Block<'feature_cards', FeatureCardsBlockData>;
export type ProcessStepsBlock = Block<'process_steps', ProcessStepsBlockData>;
export type TimelineBlock = Block<'timeline', TimelineBlockData>;
export type TabbedPillarsBlock = Block<
    'tabbed_pillars',
    TabbedPillarsBlockData
>;
export type QuoteBlock = Block<'quote', QuoteBlockData>;
export type ParallaxBandBlock = Block<'parallax_band', ParallaxBandBlockData>;

export type TeamGridBlock = Block<'team_grid', TeamGridBlockData>;
export type FaqBlock = Block<'faq', FaqBlockData>;
export type GalleryBlock = Block<'gallery', GalleryBlockData>;
export type VideoBlock = Block<'video', VideoBlockData>;
export type AwardsBlock = Block<'awards', AwardsBlockData>;
export type LocationsGridBlock = Block<
    'locations_grid',
    LocationsGridBlockData
>;
export type OfficesBlock = Block<'offices', OfficesBlockData>;
export type FormBlock = Block<'form', FormBlockData>;

export type PageBlock =
    | HeroBlock
    | PageHeaderBlock
    | IntroStatementBlock
    | FeaturedProjectsBlock
    | ProjectGridBlock
    | SegmentTilesBlock
    | StatsCountersBlock
    | ProgressHighlightBlock
    | TestimonialsBlock
    | PartnersMarqueeBlock
    | LatestNewsBlock
    | CtaBandBlock
    | RichTextBlock
    | ImageTextBlock
    | FeatureCardsBlock
    | ProcessStepsBlock
    | TimelineBlock
    | TabbedPillarsBlock
    | QuoteBlock
    | ParallaxBandBlock
    | TeamGridBlock
    | FaqBlock
    | GalleryBlock
    | VideoBlock
    | AwardsBlock
    | LocationsGridBlock
    | OfficesBlock
    | FormBlock;

export type PageBlockType = PageBlock['type'];
