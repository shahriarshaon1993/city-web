<script setup lang="ts">
import { computed } from 'vue';
import EmptyState from '@/components/site/EmptyState.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import type { StyleguideImages } from '@/components/styleguide/types';
import type { ImagePreset } from '@/lib/images';
import type { MediaImage } from '@/types/site';

// ARC-016: one specimen per `sizes` preset (design/foundations.md §5.3). The
// hero is the page's only `fetchpriority="high"` image (SEO-PERF-006).
const props = defineProps<{
    images: StyleguideImages;
    ogImage: string | null;
}>();

const tiles = computed<
    { preset: ImagePreset; image: MediaImage | null; caption: string }[]
>(() => [
    {
        preset: 'project-card',
        image: props.images.project,
        caption: '4:3 · lazy',
    },
    { preset: 'news-card', image: props.images.news, caption: '16:10 · lazy' },
    {
        preset: 'segment-tile',
        image: props.images.location,
        caption: '16:9 → 3:4 at 1024 px',
    },
    {
        preset: 'team-portrait',
        image: props.images.portrait,
        caption: '4:5 · focal 50,35',
    },
    {
        preset: 'floor-plan',
        image: props.images.interior,
        caption: 'intrinsic · contain',
    },
    { preset: 'project-card', image: null, caption: 'no image: placeholder' },
]);
</script>

<template>
    <StyleguideSection id="sg-images" title="Images">
        <EmptyState
            v-if="!images.hero"
            class="rounded-xl border"
            title="No demo images yet"
            body="Run php artisan demo:images --placeholders, then reload."
        />
        <template v-else>
            <figure class="flex flex-col gap-2">
                <div class="relative h-96 overflow-hidden rounded-xl md:h-128">
                    <ResponsiveImage
                        :image="images.hero"
                        :mobile="images.hero_mobile"
                        preset="hero"
                        priority
                    />
                </div>
                <figcaption class="text-body text-muted-foreground">
                    <code>hero</code> · eager ·
                    <code>fetchpriority="high"</code> (the page's only one) ·
                    portrait crop below 768 px
                </figcaption>
            </figure>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <figure
                    v-for="(tile, index) in tiles"
                    :key="index"
                    class="flex flex-col gap-2"
                >
                    <ResponsiveImage
                        :image="tile.image"
                        :preset="tile.preset"
                        class="rounded-lg"
                    />
                    <figcaption class="text-body text-muted-foreground">
                        <code>{{ tile.preset }}</code> {{ tile.caption }}
                    </figcaption>
                </figure>
                <figure class="flex flex-col gap-2 sm:col-span-2">
                    <div class="grid grid-cols-4 gap-2">
                        <ResponsiveImage
                            v-for="(thumb, index) in images.thumbs"
                            :key="index"
                            :image="thumb"
                            preset="gallery-thumb"
                            class="rounded-md"
                        />
                    </div>
                    <figcaption class="text-body text-muted-foreground">
                        <code>gallery-thumb</code> 1:1 ·
                        <code>sizes="160px"</code>
                        <template v-if="ogImage">
                            ·
                            <a :href="ogImage" class="text-primary underline">
                                og.jpg 1200×630
                            </a>
                        </template>
                    </figcaption>
                </figure>
            </div>
        </template>
    </StyleguideSection>
</template>
