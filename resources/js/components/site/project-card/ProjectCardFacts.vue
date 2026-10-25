<script setup lang="ts">
import { Bath, BedDouble, LandPlot, Ruler } from '@lucide/vue';
import type { Component } from 'vue';
import { computed } from 'vue';
import { formatKatha, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01 facts as a spec sheet: a <dl>, never headings (SEO-ONP-003).
// Hairlines come from each cell's top/left border, clipped by the frame, so
// they stay correct however the grid wraps: 2 × 2 on narrow cards (the spec's
// 340 px switch, widened for labelled cells), one row once every value fits
// unclipped (columns size to their content).
const props = withDefaults(
    defineProps<{
        project: ProjectCard;
        /** `media`: frosted glass over a photo (featured variant). */
        tone?: 'surface' | 'media';
    }>(),
    { tone: 'surface' },
);

type Fact = {
    key: string;
    label: string;
    icon: Component;
    value: string;
    unit?: string;
};

function range(min: number | null, max: number | null): string | null {
    if (min === null) {
        return null;
    }

    return max === null || max === min
        ? formatNumber(min)
        : `${formatNumber(min)}–${formatNumber(max)}`;
}

const facts = computed<Fact[]>(() => {
    const p = props.project;
    const size = range(p.size_min_sft, p.size_max_sft);
    const beds = range(p.beds_min, p.beds_max);
    const baths = range(p.baths_min, p.baths_max);
    const list: Fact[] = [];

    if (size !== null) {
        list.push({
            key: 'size',
            label: 'Size',
            icon: Ruler,
            value: size,
            unit: 'sft',
        });
    }

    if (beds !== null) {
        list.push({ key: 'beds', label: 'Beds', icon: BedDouble, value: beds });
    }

    if (baths !== null) {
        list.push({ key: 'baths', label: 'Baths', icon: Bath, value: baths });
    }

    if (p.land_katha !== null) {
        list.push({
            key: 'land',
            label: 'Land',
            icon: LandPlot,
            value: formatKatha(p.land_katha),
        });
    }

    return list;
});

const media = computed(() => props.tone === 'media');
</script>

<template>
    <div
        v-if="facts.length > 0"
        :class="
            cn(
                '@container overflow-hidden rounded-lg',
                media
                    ? 'bg-white/10 ring-1 ring-white/15 backdrop-blur-md ring-inset'
                    : 'bg-surface-subtle ring-1 ring-border ring-inset',
            )
        "
    >
        <dl
            :class="
                cn(
                    '-mt-px -ml-px grid grid-cols-[repeat(2,minmax(max-content,1fr))]',
                    facts.length === 4
                        ? '@min-[26rem]:auto-cols-[minmax(max-content,1fr)] @min-[26rem]:grid-flow-col @min-[26rem]:grid-cols-none'
                        : '@min-[19rem]:auto-cols-[minmax(max-content,1fr)] @min-[19rem]:grid-flow-col @min-[19rem]:grid-cols-none',
                )
            "
        >
            <div
                v-for="(fact, index) in facts"
                :key="fact.key"
                :class="
                    cn(
                        'flex flex-col gap-0.5 border-t border-l px-3 py-2.5',
                        media ? 'border-white/15' : 'border-border',
                        index === facts.length - 1 &&
                            facts.length % 2 === 1 &&
                            'col-span-2 @min-[19rem]:col-span-1',
                    )
                "
            >
                <dt
                    :class="
                        cn(
                            'flex items-center gap-1.5 text-xs',
                            media ? 'text-white/70' : 'text-muted-foreground',
                        )
                    "
                >
                    <component
                        :is="fact.icon"
                        class="size-3.5 shrink-0"
                        aria-hidden="true"
                    />
                    {{ fact.label }}
                </dt>
                <dd class="tabular text-sm font-semibold whitespace-nowrap">
                    {{ fact.value }}
                    <span
                        v-if="fact.unit"
                        :class="
                            cn(
                                'text-xs font-medium',
                                media
                                    ? 'text-white/70'
                                    : 'text-muted-foreground',
                            )
                        "
                    >
                        {{ fact.unit }}
                    </span>
                </dd>
            </div>
        </dl>
    </div>
</template>
