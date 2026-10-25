// Public props contract (ARC-002, DEC-29): shaped like the Part B API
// Resources; Part A fills them from App\Support\Demo\DemoContent.

import type { BookingValue, LifecycleValue } from '@/components/ui/badge';

/**
 * One image with its conversions (CMS-MED-004/005, HERO-011). Output by
 * App\Support\Demo\DemoMedia now and by the media resource in 11.1 (DEC-31).
 */
export type MediaImage = {
    /** Already `''` when decorative (HERO-014). */
    alt: string;
    decorative: boolean;
    caption: string | null;
    /** e.g. "Artist's impression" (design/foundations.md §5.2). */
    credit: string | null;
    /** Intrinsic size of the original. */
    width: number;
    height: number;
    /** Focal point in percent, applied as `object-position` (HERO-013). */
    focal: { x: number; y: number };
    /** Average colour of `thumb`, `#rrggbb` (design/foundations.md §5.4). */
    dominant_color: string | null;
    /** `srcset` strings ("url 640w, url 960w"); `avif` null when GD lacks it. */
    sources: {
        avif: string | null;
        webp: string;
        jpeg: string | null;
    };
    /** JPEG URL for `<img src>`. */
    fallback: string;
};

/** Hero background video (HERO-020): MP4 required, WebM optional; no audio. */
export type MediaVideo = {
    mp4: string;
    webm: string | null;
};

/** HERO-030/031: the page's LCP image, preloaded by the root view. */
export type LcpImage = {
    image: MediaImage;
    mobile: MediaImage | null;
};

export type ProjectSegment =
    | 'residential'
    | 'commercial'
    | 'mixed_use'
    | 'land';

/** CMP-CARD-01 (ProjectCardResource). */
export type ProjectCard = {
    id: number;
    slug: string;
    url: string;
    name: string;
    segment: ProjectSegment;
    segment_label: string;
    lifecycle: LifecycleValue;
    lifecycle_label: string;
    booking_state: BookingValue | 'none';
    booking_label: string | null;
    is_featured: boolean;
    location: { area: string; city: string };
    cover: MediaImage | null;
    size_min_sft: number | null;
    size_max_sft: number | null;
    beds_min: number | null;
    beds_max: number | null;
    baths_min: number | null;
    baths_max: number | null;
    land_katha: number | null;
    /** Null when the price is hidden: it never leaves the server (AC-PROJ-01). */
    price_from_bdt: number | null;
    price_note: string | null;
};

/** CMP-NEWS-01 (NewsCardResource). */
export type NewsCard = {
    id: number;
    slug: string;
    url: string;
    title: string;
    excerpt: string;
    cover: MediaImage | null;
    category: { name: string; slug: string; url: string };
    /** ISO date. */
    published_at: string;
    /** FR-NEWS-004: shown on PG-14 only, never on home. */
    read_minutes: number;
};

/** CMP-TEST-01 (TestimonialResource, FR-SOC-001): text only, no rating. */
export type Testimonial = {
    id: number;
    quote: string;
    person_name: string;
    designation: string | null;
    project: { name: string; url: string } | null;
    /** Square ≥ 400 px; null shows the initials (DEC-47). */
    photo: MediaImage | null;
    is_nrb: boolean;
};

/** PartnerResource (FR-SOC-002). An SVG logo has empty `sources` (DEC-47). */
export type Partner = {
    id: number;
    name: string;
    logo: MediaImage | null;
    /** Rendered with `rel="noopener nofollow"`. */
    url: string | null;
    category: 'bank' | 'supplier' | 'consultant' | 'group_company';
};

export type ProgressStage =
    | 'foundation'
    | 'structure'
    | 'finishing'
    | 'complete';

/** CMP-PROG-01 summary: a project's latest DM-PROG update. */
export type ProgressSummary = {
    project: {
        id: number;
        name: string;
        url: string;
        location: { area: string; city: string };
    };
    percent: number;
    stage: ProgressStage;
    stage_label: string;
    /** ISO date (month precision). */
    reported_on: string;
    note: string | null;
    photo: MediaImage | null;
};

export type Robots = 'index,follow' | 'noindex,follow' | 'noindex,nofollow';

/** One `<link rel="preload" as="image">` (HERO-030/031). */
export type ImagePreload = {
    srcset: string;
    sizes: string;
    type: 'image/avif' | 'image/webp';
    media: string | null;
};

/**
 * `seo` page prop (App\Support\Seo\SeoData), rendered into the head by the
 * root view and SeoHead.vue (SEO-REN-01, cms.md §8.5).
 */
export type Seo = {
    /** Final `<title>`, template already applied (SEO-ONP-001). */
    title: string;
    description: string | null;
    /**
     * Absolute clean URL (SEO-TEC-004); null on pages that are noindex by
     * themselves (not by the non-production override of `robots`).
     */
    canonical: string | null;
    robots: Robots;
    og: {
        type: 'website' | 'article';
        title: string;
        description: string | null;
        url: string;
        /** Absolute 1200×630 URL (SEO-ONP-006). */
        image: string | null;
        image_alt: string | null;
        site_name: string;
        locale: string;
    };
    twitter_card: 'summary' | 'summary_large_image';
    /** `[]` until 19.1. */
    json_ld: Record<string, unknown>[];
    /** Used by the root view only; empty without an LCP image. */
    preload: ImagePreload[];
};

/** CMS-NAV-002; URLs are resolved server-side (CMS-NAV-004). */
export type MenuItem = {
    id: number;
    label: string;
    url: string;
    new_tab: boolean;
    /** Lucide name from the allowlist in `lib/icons.ts`. */
    icon: string | null;
    description: string | null;
    /** `auto:locations` only: published projects in the area (CMS-NAV-003). */
    count: number | null;
    children: MenuItem[];
    /** CMS-NAV-003: header items only. */
    mega: MegaColumn[] | null;
};

export type MegaColumn =
    | {
          kind: 'links' | 'segments' | 'locations';
          heading: string | null;
          items: MenuItem[];
      }
    | {
          kind: 'featured_projects';
          heading: string | null;
          projects: ProjectCard[];
      };

export type MenuKey =
    | 'header'
    | 'footer_1'
    | 'footer_2'
    | 'footer_3'
    | 'footer_4'
    | 'legal'
    | 'mobile_extra';

/** `site` shared prop (ARC-003, cms.md §8.2 General/Contact/Social). */
export type SiteInfo = {
    name: string;
    tagline: string | null;
    logos: { light: string; dark: string; mark: string };
    contact: {
        hotline: string | null;
        phones: string[];
        emails: string[];
        /** E.164. */
        whatsapp: string | null;
        office_hours: string | null;
    };
    social: Partial<
        Record<'facebook' | 'instagram' | 'linkedin' | 'youtube' | 'x', string>
    >;
    parent_company: { name: string; url: string | null } | null;
    /** Settings → Header (cms.md §8.2). */
    header: {
        sticky: boolean;
        transparent_on_hero: boolean;
        cta: { label: string; url: string };
        show_shortlist: boolean;
        show_phone: boolean;
    };
    /** Settings → Footer; `{year}` already replaced in `copyright`. */
    footer: {
        about: string;
        newsletter: boolean;
        copyright: string;
        show_parent_company: boolean;
        newsletter_title: string;
        newsletter_text: string;
    };
};

/** DM-OFF (OfficeResource). */
export type Office = {
    id: number;
    name: string;
    address: string;
    city: string;
    postal_code: string | null;
    /** E.164. */
    phones: string[];
    email: string | null;
    hours: { days: string[]; opens: string; closes: string }[];
    latitude: number;
    longitude: number;
    is_head_office: boolean;
};

/** `footer` shared prop (CMP-FOOT-01). */
export type FooterInfo = {
    /** Headings (menu names) of the `footer_1…4` menus, in order. */
    columns: { menu: MenuKey; heading: string }[];
    offices_heading: string;
    offices: Office[];
    locations_heading: string;
    /** Top 8 areas (SEO-ONP-005). */
    locations: MenuItem[];
};

/** CMP-BREAD-01: Home first; the current page last, `url` null. */
export type Breadcrumb = {
    label: string;
    url: string | null;
};

/** Shared props of every public page (ARC-003, HandleInertiaRequests). */
export type SiteShared = {
    site: SiteInfo;
    menus: Record<MenuKey, MenuItem[]>;
    footer: FooterInfo;
    seo: Seo;
    ui: {
        /** Settings → Motion (MOT-R03). */
        motion: {
            animations: boolean;
            smooth_scroll: boolean;
            hero_video_mobile: boolean;
        };
        /** Stored choice, null until the visitor decides. */
        consent: {
            version: number;
            analytics: boolean;
            marketing: boolean;
        } | null;
    };
};

/** Per-page props the public layout reads besides the shared ones. */
export type SitePageProps = SiteShared & {
    /** CMP-HEAD-01: the first block is a hero, so the header may start transparent. */
    starts_with_hero?: boolean;
};

export type ErrorStatus = 404 | 410 | 419 | 429 | 500 | 503;

/**
 * `Site/Error` props (PG-26, App\Support\Errors\ErrorPage). With `minimal`
 * (500/503) only `site` and `seo` of the shared props are present.
 */
export type ErrorPageProps = {
    status: ErrorStatus;
    title: string;
    message: string;
    /** 503 only: expected return time. */
    retry_text: string | null;
    /** 404 only: PG-19 site search. */
    search: { action: string; name: string; min_length: number } | null;
    /** 404 only. */
    popular: { heading: string; projects: ProjectCard[] } | null;
    minimal: boolean;
};
