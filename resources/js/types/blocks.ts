// Page-builder blocks (CMS-PB-001, DM-BLOCK), shaped like the Part B block
// resources (ARC-002): editor-only fields (manual IDs, preset filters, limit)
// are resolved server-side by the block data loaders and not sent.

import type { ProjectSearchOptions } from '@/types/search';
import type {
    MediaImage,
    MediaVideo,
    ProjectCard,
    ProjectSegment,
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

export type PageBlock =
    | HeroBlock
    | IntroStatementBlock
    | FeaturedProjectsBlock
    | ProjectGridBlock
    | SegmentTilesBlock
    | StatsCountersBlock;

export type PageBlockType = PageBlock['type'];
