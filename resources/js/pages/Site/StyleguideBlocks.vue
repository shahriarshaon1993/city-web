<script setup lang="ts">
import BlockRenderer from '@/components/blocks/BlockRenderer.vue';
import type { PageBlock } from '@/types/blocks';

// Local-only content block reference (6.1): every block of 6.1 rendered with
// demo data through the same BlockRenderer the builder pages use (6.3).
// `?header=plain` swaps the photo header for the plain one.
defineProps<{
    blocks: PageBlock[];
}>();
</script>

<template>
    <BlockRenderer :blocks="blocks" />
</template>
