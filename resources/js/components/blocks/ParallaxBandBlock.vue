<script setup lang="ts">
import { computed } from 'vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { cn } from '@/lib/utils';
import type { ParallaxBandBlock } from '@/types/blocks';

// CMP-BLK-PARALLAX: a full-bleed photo band, 60svh (`md`) or 80svh (`lg`),
// with an optional heading and line of text on a bottom scrim (white text over
// ≥ 35 % overlay, direction.md §3). MOT-PARALLAX 10 % when `parallax` is on
// (halved on touch; static under reduced motion — the layer overflows the
// frame from first paint, so the crop is identical). Intended for spacing
// `none`. Heading is an H2 (H1 only as the first block, CMS-PB-004).
const props = defineProps<{
    block: ParallaxBandBlock;
    first: boolean;
}>();

const hasText = computed(
    () => props.block.data.heading !== null || props.block.data.text !== null,
);
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        v-parallax="block.data.parallax ? 10 : 0"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
        width="full"
        :section-class="
            cn(
                'theme-dark relative isolate flex overflow-hidden bg-stone-950 [--overlay:0.6]',
                block.data.height === 'lg'
                    ? 'h-[max(80svh,28rem)]'
                    : 'h-[max(60svh,24rem)]',
            )
        "
        class="flex w-full"
    >
        <div
            data-parallax-layer
            class="absolute inset-x-0 -inset-y-[10%] -z-10"
        >
            <ResponsiveImage
                :image="block.data.image"
                preset="cta-band"
                :priority="first"
                class="absolute inset-0"
            />
            <div
                v-if="hasText"
                data-overlay
                class="absolute inset-0 bg-(image:--gradient-hero-bottom)"
                aria-hidden="true"
            />
        </div>

        <div
            v-if="hasText"
            class="mx-auto flex w-full max-w-content items-end px-4 pb-10 sm:px-6 lg:px-8 lg:pb-16 xl:px-10"
        >
            <div
                v-reveal
                class="flex max-w-3xl flex-col gap-4 [text-shadow:0_1px_2px_rgb(0_0_0/0.35)]"
            >
                <component
                    :is="headingTag"
                    v-if="block.data.heading"
                    :id="headingId"
                    class="text-h2 text-balance text-white"
                >
                    {{ block.data.heading }}
                </component>
                <p
                    v-if="block.data.text"
                    class="max-w-prose text-lead text-pretty text-white/90"
                >
                    {{ block.data.text }}
                </p>
            </div>
        </div>
    </BlockSection>
</template>
