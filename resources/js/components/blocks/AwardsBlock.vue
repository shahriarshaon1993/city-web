<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { AwardsBlock } from '@/types/blocks';

// CMP-BLK-AWARDS: a closed hairline grid (2 / 3 / 4 across) — each cell holds
// the award's mark or certificate on top, then the light title, and an
// overline "issuer · year" line. No card surfaces, no shadows. Award titles
// are H3 under the block's H2 (H2 without a block heading). The grid's outer
// edge is drawn by the list (top + left) and each cell (right + bottom), so
// a partly filled last row still closes cleanly.
const props = defineProps<{
    block: AwardsBlock;
    first: boolean;
}>();

const titleTag = computed(() => (props.block.data.heading ? 'h3' : 'h2'));

function meta(issuer: string | null, year: number | null): string {
    return [issuer, year].filter((part) => part !== null).join(' · ');
}
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
            v-reveal.stagger
            class="grid grid-cols-2 border-t border-l border-border md:grid-cols-3 xl:grid-cols-4"
        >
            <li
                v-for="(item, index) in block.data.items"
                :key="index"
                class="flex flex-col gap-6 border-r border-b border-border p-5 sm:p-8"
            >
                <span class="flex h-14 items-center">
                    <ResponsiveImage
                        :image="item.image"
                        preset="partner-logo"
                        class="h-12 w-auto max-w-full"
                    />
                </span>
                <div class="flex flex-col gap-2">
                    <component
                        :is="titleTag"
                        class="font-display text-h4 text-balance"
                    >
                        {{ item.title }}
                    </component>
                    <p
                        v-if="item.issuer || item.year"
                        class="text-overline text-muted-foreground uppercase"
                    >
                        {{ meta(item.issuer, item.year) }}
                    </p>
                </div>
            </li>
        </ul>
    </BlockSection>
</template>
