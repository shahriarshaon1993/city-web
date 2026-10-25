<script setup lang="ts">
import HeroSingle from '@/components/blocks/hero/HeroSingle.vue';
import HeroSlider from '@/components/blocks/hero/HeroSlider.vue';
import type { HeroBlock } from '@/types/blocks';

// CMP-BLK-HERO (CMP-HERO-01/02/03 + CMP-SEARCH-01). A slider needs at
// least two slides (HERO-003); with fewer it renders as a single image.
const props = defineProps<{
    block: HeroBlock;
    /** First block of the page (CMS-PB-004): H1 and the LCP image. */
    first: boolean;
}>();

const isSlider = () =>
    props.block.data.mode === 'slider' && props.block.data.slides.length >= 2;
</script>

<template>
    <HeroSlider
        v-if="isSlider()"
        :id="block.anchor_id ?? undefined"
        :data="block.data"
        :first="first"
    />
    <HeroSingle
        v-else
        :id="block.anchor_id ?? undefined"
        :data="
            block.data.mode === 'slider'
                ? {
                      ...block.data,
                      image: block.data.slides[0]?.image ?? block.data.image,
                      mobile_image:
                          block.data.slides[0]?.mobile_image ??
                          block.data.mobile_image,
                  }
                : block.data
        "
        :first="first"
    />
</template>
