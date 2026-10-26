<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { SegmentTilesBlock } from '@/types/blocks';

// CMP-BLK-SEGMENTS: 2–4 image tiles linking to the segment pages. ≥ 1024 one
// row of tall tiles (row height fixed, so the hover expand 1 → 1.4 never
// shifts the page); 768–1023 a 2 × 2 grid; below that stacked 16:9 (LAY-HOME).
defineProps<{
    block: SegmentTilesBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        />
        <ul
            class="grid gap-4 md:grid-cols-2 lg:flex lg:h-[clamp(26rem,34vw,34rem)] lg:gap-6"
        >
            <li
                v-for="(tile, index) in block.data.tiles"
                :key="tile.segment"
                class="lg:min-w-0 lg:flex-[1_1_0%] lg:transition-[flex-grow] lg:duration-(--dur-slow) lg:ease-(--ease-standard) lg:focus-within:grow-[1.4] lg:hover:grow-[1.4]"
            >
                <Link
                    v-mask="index * 75"
                    :href="tile.url"
                    class="segment-tile group/tile relative isolate flex h-full overflow-hidden rounded-xl"
                >
                    <ResponsiveImage
                        :image="tile.image"
                        preset="segment-tile"
                        img-class="media-zoom"
                        class="w-full lg:absolute lg:inset-0 lg:aspect-auto lg:h-full"
                    />
                    <span
                        class="absolute inset-0 bg-[linear-gradient(to_top,rgb(16_12_11/0.75),rgb(16_12_11/0.15)_55%,transparent)]"
                        aria-hidden="true"
                    />
                    <div
                        class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-4 p-5 text-white [text-shadow:0_1px_2px_rgb(0_0_0/0.35)] lg:p-6"
                    >
                        <div class="flex min-w-0 flex-col gap-1">
                            <h3 class="text-h3">{{ tile.label }}</h3>
                            <p
                                v-if="tile.description"
                                class="text-sm text-white/90"
                            >
                                {{ tile.description }}
                            </p>
                        </div>
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-full bg-white/15 backdrop-blur-[8px] transition-transform duration-(--dur-base) group-hover/tile:translate-x-1"
                            aria-hidden="true"
                        >
                            <ArrowRight class="size-5" />
                        </span>
                    </div>
                </Link>
            </li>
        </ul>
    </BlockSection>
</template>
