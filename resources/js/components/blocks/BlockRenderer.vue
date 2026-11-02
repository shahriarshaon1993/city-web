<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import type { Component } from 'vue';
import HeroBlock from '@/components/blocks/HeroBlock.vue';
import type { PageBlock, PageBlockType } from '@/types/blocks';

// CMS-PB-001: renders an ordered block list. Hidden blocks are skipped and
// the first visible block owns the H1 (CMS-PB-004). Each type is its own
// lazy chunk, except the hero: it is the usual first block and holds the LCP
// image, so it ships with the page instead of after an extra request. A first
// `page_header` photo is preloaded by the root view instead (SeoData::lcpFromBlocks).
const props = defineProps<{
    blocks: PageBlock[];
}>();

const components: Record<PageBlockType, Component> = {
    hero: HeroBlock,
    intro_statement: defineAsyncComponent(
        () => import('@/components/blocks/IntroStatementBlock.vue'),
    ),
    featured_projects: defineAsyncComponent(
        () => import('@/components/blocks/FeaturedProjectsBlock.vue'),
    ),
    project_grid: defineAsyncComponent(
        () => import('@/components/blocks/ProjectGridBlock.vue'),
    ),
    segment_tiles: defineAsyncComponent(
        () => import('@/components/blocks/SegmentTilesBlock.vue'),
    ),
    stats_counters: defineAsyncComponent(
        () => import('@/components/blocks/StatsCountersBlock.vue'),
    ),
    progress_highlight: defineAsyncComponent(
        () => import('@/components/blocks/ProgressHighlightBlock.vue'),
    ),
    testimonials: defineAsyncComponent(
        () => import('@/components/blocks/TestimonialsBlock.vue'),
    ),
    partners_marquee: defineAsyncComponent(
        () => import('@/components/blocks/PartnersMarqueeBlock.vue'),
    ),
    latest_news: defineAsyncComponent(
        () => import('@/components/blocks/LatestNewsBlock.vue'),
    ),
    cta_band: defineAsyncComponent(
        () => import('@/components/blocks/CtaBandBlock.vue'),
    ),
    page_header: defineAsyncComponent(
        () => import('@/components/blocks/PageHeaderBlock.vue'),
    ),
    rich_text: defineAsyncComponent(
        () => import('@/components/blocks/RichTextBlock.vue'),
    ),
    image_text: defineAsyncComponent(
        () => import('@/components/blocks/ImageTextBlock.vue'),
    ),
    feature_cards: defineAsyncComponent(
        () => import('@/components/blocks/FeatureCardsBlock.vue'),
    ),
    process_steps: defineAsyncComponent(
        () => import('@/components/blocks/ProcessStepsBlock.vue'),
    ),
    timeline: defineAsyncComponent(
        () => import('@/components/blocks/TimelineBlock.vue'),
    ),
    tabbed_pillars: defineAsyncComponent(
        () => import('@/components/blocks/TabbedPillarsBlock.vue'),
    ),
    quote: defineAsyncComponent(
        () => import('@/components/blocks/QuoteBlock.vue'),
    ),
    parallax_band: defineAsyncComponent(
        () => import('@/components/blocks/ParallaxBandBlock.vue'),
    ),
    team_grid: defineAsyncComponent(
        () => import('@/components/blocks/TeamGridBlock.vue'),
    ),
    faq: defineAsyncComponent(() => import('@/components/blocks/FaqBlock.vue')),
    gallery: defineAsyncComponent(
        () => import('@/components/blocks/GalleryBlock.vue'),
    ),
    video: defineAsyncComponent(
        () => import('@/components/blocks/VideoBlock.vue'),
    ),
    awards: defineAsyncComponent(
        () => import('@/components/blocks/AwardsBlock.vue'),
    ),
    locations_grid: defineAsyncComponent(
        () => import('@/components/blocks/LocationsGridBlock.vue'),
    ),
    offices: defineAsyncComponent(
        () => import('@/components/blocks/OfficesBlock.vue'),
    ),
    form: defineAsyncComponent(
        () => import('@/components/blocks/FormBlock.vue'),
    ),
};

const visible = computed(() =>
    props.blocks.filter(
        (block) => block.is_visible && components[block.type] !== undefined,
    ),
);
</script>

<template>
    <div>
        <component
            :is="components[block.type]"
            v-for="(block, index) in visible"
            :key="block.id"
            :block="block"
            :first="index === 0"
        />
    </div>
</template>
