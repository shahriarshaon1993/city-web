<script setup lang="ts">
import BlockRenderer from '@/components/blocks/BlockRenderer.vue';
import type { PageBlock } from '@/types/blocks';

// Builder pages (PG-07…11) and custom pages (PG-25): the page is its ordered
// block list (CMS-PB-001); the first block owns the H1 and, as a hero or a
// photo page header, the LCP image and the transparent header
// (`starts_with_hero`).
defineProps<{
    blocks: PageBlock[];
}>();
</script>

<template>
    <BlockRenderer :blocks="blocks" />
</template>
