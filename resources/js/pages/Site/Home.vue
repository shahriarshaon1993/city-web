<script setup lang="ts">
import HeroBlock from '@/components/blocks/HeroBlock.vue';
import type { PageBlock } from '@/types/blocks';

// PG-01 (LAY-HOME): the block list, hero first; its image is the page's LCP
// image (preloaded by the root view, HERO-030) and the header sits over it
// (`starts_with_hero`). 2.2 replaces the loop with the block renderer.
defineProps<{
    blocks: PageBlock[];
    lead: string;
}>();
</script>

<template>
    <div>
        <template v-for="(block, index) in blocks" :key="block.id">
            <HeroBlock
                v-if="block.type === 'hero'"
                :block="block"
                :first="index === 0"
            />
        </template>
        <div class="mx-auto flex min-h-[70svh] max-w-3xl flex-col px-6 py-16">
            <p class="text-lg text-muted-foreground">{{ lead }}</p>
        </div>
    </div>
</template>
