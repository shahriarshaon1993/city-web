<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { StatsCountersBlock } from '@/types/blocks';

// CMP-BLK-STATS / CMP-STAT-01: 2 cols < 768, 3 at 768–1023, all items in one
// row ≥ 1024 with dividers. Final values are in the HTML (SEO); the visible
// number is aria-hidden with a visually hidden twin, so the count-up of 3.1
// (MOT-COUNTER, `data-counter`) is never read out. `min-width` in ch reserves
// the final width (no CLS). Stats are a <dl>, never headings.
const props = defineProps<{
    block: StatsCountersBlock;
    first: boolean;
}>();

const stats = computed(() =>
    props.block.data.items.map((item) => ({
        ...item,
        display: formatNumber(item.value, item.decimals),
    })),
);

const desktopColumns: Record<number, string> = {
    2: 'lg:grid-cols-2',
    3: 'lg:grid-cols-3',
    4: 'lg:grid-cols-4',
    5: 'lg:grid-cols-5',
    6: 'lg:grid-cols-6',
};
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
        <dl
            :class="
                cn(
                    'grid grid-cols-2 gap-x-6 gap-y-10 md:grid-cols-3 lg:gap-x-0 lg:divide-x lg:divide-border',
                    desktopColumns[stats.length],
                )
            "
        >
            <div
                v-for="(stat, index) in stats"
                :key="index"
                class="flex flex-col-reverse gap-2 lg:px-8 lg:first:pl-0"
            >
                <dt class="text-sm text-muted-foreground">{{ stat.label }}</dt>
                <dd class="font-display text-stat">
                    <span aria-hidden="true" class="tabular">
                        <span
                            data-counter
                            :data-value="stat.value"
                            :data-decimals="stat.decimals"
                            class="inline-block"
                            :style="{ minWidth: `${stat.display.length}ch` }"
                            >{{ stat.display }}</span
                        >{{ stat.suffix }}
                    </span>
                    <span class="sr-only"
                        >{{ stat.display }}{{ stat.suffix }}</span
                    >
                </dd>
            </div>
        </dl>
    </BlockSection>
</template>
