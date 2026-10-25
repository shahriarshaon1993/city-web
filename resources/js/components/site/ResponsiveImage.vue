<script setup lang="ts">
import { computed } from 'vue';
import type { HTMLAttributes, StyleValue } from 'vue';
import { imagePresets } from '@/lib/images';
import type { ImagePreset, ImagePresetConfig } from '@/lib/images';
import { cn } from '@/lib/utils';
import type { MediaImage } from '@/types/site';

// The only way to render a content image (golden rule 3): <picture> with
// AVIF/WebP sources, exact `sizes` preset (design/foundations.md §5.3),
// width/height, lazy below the fold, one `fetchpriority="high"` per page
// (SEO-PERF-006, HERO-012). The box shows the dominant colour + hexagon
// until the image paints over it, without JS (§5.4).
const props = withDefaults(
    defineProps<{
        /** Null renders the placeholder only. */
        image: MediaImage | null;
        preset: ImagePreset;
        /** Portrait crop served below 768 px (HERO-013). */
        mobile?: MediaImage | null;
        /** The page's LCP image: eager + `fetchpriority="high"`. One per page. */
        priority?: boolean;
        /** Overrides the preset, e.g. lazy for gallery slides after the first. */
        loading?: 'eager' | 'lazy';
        class?: HTMLAttributes['class'];
        imgClass?: HTMLAttributes['class'];
    }>(),
    {
        mobile: null,
        priority: false,
        loading: undefined,
        class: undefined,
        imgClass: undefined,
    },
);

const config = computed<ImagePresetConfig>(() => imagePresets[props.preset]);

const loadingAttr = computed(() =>
    props.priority ? 'eager' : (props.loading ?? config.value.loading),
);

const showMediaPlaceholder = computed(
    () => config.value.placeholder === 'media',
);

const boxClass = computed(() => {
    const { aspect, placeholder } = config.value;

    return cn(
        'relative block overflow-hidden',
        aspect === 'fill' ? 'size-full' : aspect !== 'intrinsic' && aspect,
        placeholder === 'media' && 'bg-muted',
        placeholder === 'surface' && 'bg-surface-subtle',
        props.class,
    );
});

const boxStyle = computed<StyleValue>(() => ({
    aspectRatio:
        config.value.aspect === 'intrinsic'
            ? props.image
                ? `${props.image.width} / ${props.image.height}`
                : '4 / 3'
            : undefined,
    backgroundColor:
        showMediaPlaceholder.value && props.image?.dominant_color
            ? props.image.dominant_color
            : undefined,
}));

function focal(image: MediaImage): string {
    return `${image.focal.x}% ${image.focal.y}%`;
}

const imgStyle = computed<StyleValue>(() => ({
    '--focal': props.image ? focal(props.image) : undefined,
    '--focal-m': props.mobile ? focal(props.mobile) : undefined,
}));

const imgClass = computed(() =>
    cn(
        'absolute inset-0 size-full',
        config.value.fit === 'cover'
            ? 'object-cover [object-position:var(--focal)]'
            : 'object-contain',
        props.mobile && 'max-md:[object-position:var(--focal-m,var(--focal))]',
        props.imgClass,
    ),
);

const MOBILE_MEDIA = '(max-width: 767px)';
</script>

<template>
    <span :class="boxClass" :style="boxStyle">
        <svg
            v-if="showMediaPlaceholder"
            class="absolute top-1/2 left-1/2 size-8 -translate-x-1/2 -translate-y-1/2 text-foreground opacity-20"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            aria-hidden="true"
        >
            <path d="M12 2.5 20.25 7.25v9.5L12 21.5l-8.25-4.75v-9.5z" />
        </svg>
        <picture v-if="image">
            <template v-if="mobile">
                <source
                    v-if="mobile.sources.avif"
                    :media="MOBILE_MEDIA"
                    type="image/avif"
                    :srcset="mobile.sources.avif"
                    :sizes="config.sizes"
                    :width="mobile.width"
                    :height="mobile.height"
                />
                <source
                    :media="MOBILE_MEDIA"
                    type="image/webp"
                    :srcset="mobile.sources.webp"
                    :sizes="config.sizes"
                    :width="mobile.width"
                    :height="mobile.height"
                />
            </template>
            <source
                v-if="image.sources.avif"
                type="image/avif"
                :srcset="image.sources.avif"
                :sizes="config.sizes"
            />
            <source
                type="image/webp"
                :srcset="image.sources.webp"
                :sizes="config.sizes"
            />
            <img
                :src="image.fallback"
                :srcset="image.sources.jpeg ?? undefined"
                :sizes="image.sources.jpeg ? config.sizes : undefined"
                :alt="image.decorative ? '' : image.alt"
                :width="image.width"
                :height="image.height"
                :loading="loadingAttr === 'lazy' ? 'lazy' : undefined"
                :fetchpriority="priority ? 'high' : undefined"
                decoding="async"
                :class="imgClass"
                :style="imgStyle"
            />
        </picture>
    </span>
</template>
