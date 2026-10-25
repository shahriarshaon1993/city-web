// Named `sizes` presets, one per row of design/foundations.md §5.3 (SEO-PERF-006).
// `sizes` strings are copied verbatim; never write a `sizes` attribute by hand.

export type ImagePresetConfig = {
    sizes: string;
    /**
     * `fill`: the parent sets the size; `intrinsic`: the image's own ratio;
     * anything else is Tailwind aspect classes.
     */
    aspect: string;
    fit: 'cover' | 'contain';
    loading: 'eager' | 'lazy';
    /** Box behind the image (§5.4): dominant colour + hexagon, a plain surface, or nothing. */
    placeholder: 'media' | 'surface' | 'none';
};

export const imagePresets = {
    /** Mobile crop (4:5) is passed as `mobile` (HERO-013). Add `priority` on the LCP slide. */
    hero: {
        sizes: '100vw',
        aspect: 'fill',
        fit: 'cover',
        loading: 'eager',
        placeholder: 'media',
    },
    /** DEC-46: 4:5 portrait (towers), not §5.3's 4:3. */
    'project-card': {
        sizes: '(min-width:1280px) 400px, (min-width:768px) 45vw, 92vw',
        aspect: 'aspect-[4/5]',
        fit: 'cover',
        loading: 'lazy',
        placeholder: 'media',
    },
    /** Mega menu `auto:featured_projects` thumbnail (DEC-40; not in §5.3). */
    'mega-card': {
        sizes: '112px',
        aspect: 'aspect-[4/3]',
        fit: 'cover',
        loading: 'lazy',
        placeholder: 'media',
    },
    /** Eager for the first slide only; pass `loading="lazy"` for the rest. */
    'gallery-hero': {
        sizes: '(min-width:1440px) 1440px, 100vw',
        aspect: 'aspect-[4/3] md:aspect-video',
        fit: 'cover',
        loading: 'eager',
        placeholder: 'media',
    },
    'gallery-thumb': {
        sizes: '160px',
        aspect: 'aspect-square',
        fit: 'cover',
        loading: 'lazy',
        placeholder: 'media',
    },
    'floor-plan': {
        sizes: '(min-width:1024px) 640px, 92vw',
        aspect: 'intrinsic',
        fit: 'contain',
        loading: 'lazy',
        placeholder: 'surface',
    },
    'news-card': {
        sizes: '(min-width:1280px) 400px, (min-width:768px) 45vw, 92vw',
        aspect: 'aspect-[16/10]',
        fit: 'cover',
        loading: 'lazy',
        placeholder: 'media',
    },
    'article-cover': {
        sizes: '(min-width:1024px) 960px, 100vw',
        aspect: 'aspect-video',
        fit: 'cover',
        loading: 'eager',
        placeholder: 'media',
    },
    /** intro_statement side image, 5 of 12 columns (DEC-44; not in §5.3). */
    'intro-image': {
        sizes: '(min-width:1280px) 520px, (min-width:1024px) 40vw, 92vw',
        aspect: 'aspect-[4/5]',
        fit: 'cover',
        loading: 'lazy',
        placeholder: 'media',
    },
    'segment-tile': {
        sizes: '(min-width:1024px) 25vw, 92vw',
        aspect: 'aspect-video lg:aspect-[3/4]',
        fit: 'cover',
        loading: 'lazy',
        placeholder: 'media',
    },
    'team-portrait': {
        sizes: '(min-width:1024px) 280px, 45vw',
        aspect: 'aspect-[4/5]',
        fit: 'cover',
        loading: 'lazy',
        placeholder: 'media',
    },
    /** Consumer sets the height (max 48 px); width follows the ratio. */
    'partner-logo': {
        sizes: '160px',
        aspect: 'intrinsic',
        fit: 'contain',
        loading: 'lazy',
        placeholder: 'none',
    },
} satisfies Record<string, ImagePresetConfig>;

export type ImagePreset = keyof typeof imagePresets;
