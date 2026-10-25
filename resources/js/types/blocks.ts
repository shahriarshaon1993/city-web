// Page-builder blocks (CMS-PB-001, DM-BLOCK), shaped like the Part B block
// resources (ARC-002). 2.2 adds the other block types to `PageBlock`.

import type { ProjectSearchOptions } from '@/types/search';
import type { MediaImage, MediaVideo } from '@/types/site';

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

export type PageBlock = HeroBlock;
