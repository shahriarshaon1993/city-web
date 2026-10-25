<script setup lang="ts">
import { computed, defineAsyncComponent } from 'vue';
import type { Component } from 'vue';
import HeroBlock from '@/components/blocks/HeroBlock.vue';
import type { PageBlock, PageBlockType } from '@/types/blocks';

// CMS-PB-001: renders an ordered block list. Hidden blocks are skipped and
// the first visible block owns the H1 (CMS-PB-004). Each type is its own
// lazy chunk, except the hero: it is the usual first block and holds the LCP
// image, so it ships with the page instead of after an extra request.
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
