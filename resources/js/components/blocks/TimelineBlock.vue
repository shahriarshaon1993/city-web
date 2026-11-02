<script setup lang="ts">
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { TimelineBlock } from '@/types/blocks';

// CMP-BLK-TIMELINE as hairline rows (direction.md §4/§6: no year chips, no
// alternating cards): the year in light teal on the left (2 columns), the
// milestone in 5, an optional 4:3 photo in 4 from 1024 px; one stack on
// phones. Years are facts, not headings; milestone titles are H3 under the
// block's H2 (CMS-PB-004). Rows fade up one by one (MOT-REVEAL).
defineProps<{
    block: TimelineBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        />
        <ol class="border-b border-border">
            <li
                v-for="(item, index) in block.data.items"
                :key="index"
                v-reveal
                class="grid gap-x-8 gap-y-4 border-t border-border py-8 first:border-t-0 lg:grid-cols-12 lg:py-10"
            >
                <p class="tabular text-h2 text-primary lg:col-span-2">
                    {{ item.year }}
                </p>
                <div class="flex flex-col gap-3 lg:col-span-5">
                    <h3 class="text-h3 text-balance">{{ item.title }}</h3>
                    <p
                        v-if="item.text"
                        class="max-w-prose text-pretty text-muted-foreground"
                    >
                        {{ item.text }}
                    </p>
                </div>
                <ResponsiveImage
                    v-if="item.image"
                    :image="item.image"
                    preset="timeline-photo"
                    class="max-w-md rounded-xl lg:col-span-4 lg:col-start-9 lg:max-w-none"
                />
            </li>
        </ol>
    </BlockSection>
</template>
