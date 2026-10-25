<script setup lang="ts">
import BlockRenderer from '@/components/blocks/BlockRenderer.vue';
import type { PageBlock } from '@/types/blocks';

// PG-01 (LAY-HOME): the block list, hero first; its image is the page's LCP
// image (preloaded by the root view, HERO-030) and the header sits over it
// (`starts_with_hero`).
defineProps<{
    blocks: PageBlock[];
}>();
</script>

<template>
    <BlockRenderer :blocks="blocks" />
</template>
