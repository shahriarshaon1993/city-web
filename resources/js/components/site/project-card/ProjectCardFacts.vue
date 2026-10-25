<script setup lang="ts">
import { computed } from 'vue';
import { formatKatha, formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProjectCard } from '@/types/site';

// CMP-CARD-01 facts (DEC-46): one quiet line "1,650–2,100 sft · 3–4 beds ·
// 3–4 baths", wrapping when narrow. A <dl>, never headings (SEO-ONP-003); the
// term is visually hidden because the unit already names the value.
const props = withDefaults(
    defineProps<{
        project: ProjectCard;
        /** `media`: white text over a photo (featured variant). */
        tone?: 'surface' | 'media';
    }>(),
    { tone: 'surface' },
);

type Fact = { key: string; label: string; value: string; unit: string };

function range(min: number | null, max: number | null): string | null {
    if (min === null) {
        return null;
    }

    return max === null || max === min
        ? formatNumber(min)
        : `${formatNumber(min)}–${formatNumber(max)}`;
}

function plural(min: number | null, max: number | null, word: string): string {
    return (max ?? min) === 1 ? word : `${word}s`;
}

const facts = computed<Fact[]>(() => {
    const p = props.project;
    const size = range(p.size_min_sft, p.size_max_sft);
    const beds = range(p.beds_min, p.beds_max);
    const baths = range(p.baths_min, p.baths_max);
    const list: Fact[] = [];

    if (size !== null) {
        list.push({ key: 'size', label: 'Size', value: size, unit: 'sft' });
    }

    if (beds !== null) {
        list.push({
            key: 'beds',
            label: 'Bedrooms',
            value: beds,
            unit: plural(p.beds_min, p.beds_max, 'bed'),
        });
    }

    if (baths !== null) {
        list.push({
            key: 'baths',
            label: 'Bathrooms',
            value: baths,
            unit: plural(p.baths_min, p.baths_max, 'bath'),
        });
    }

    if (p.land_katha !== null) {
        list.push({
            key: 'land',
            label: 'Land',
            value: formatKatha(p.land_katha),
            unit: '',
        });
    }

    return list;
});

const media = computed(() => props.tone === 'media');
</script>

<template>
    <dl
        v-if="facts.length > 0"
        :class="
            cn(
                'flex flex-wrap items-center gap-x-3 gap-y-1 text-sm',
                media ? 'text-white/80' : 'text-muted-foreground',
            )
        "
    >
        <div
            v-for="(fact, index) in facts"
            :key="fact.key"
            class="flex items-center gap-3"
        >
            <span
                v-if="index > 0"
                :class="
                    cn(
                        'size-1 rounded-full',
                        media ? 'bg-white/50' : 'bg-stone-300',
                    )
                "
                aria-hidden="true"
            />
            <dt class="sr-only">{{ fact.label }}</dt>
            <dd class="tabular whitespace-nowrap">
                <span :class="media ? 'text-white' : 'text-foreground'">{{
                    fact.value
                }}</span>
                <template v-if="fact.unit">&nbsp;{{ fact.unit }}</template>
            </dd>
        </div>
    </dl>
</template>
