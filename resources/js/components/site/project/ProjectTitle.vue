<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import { computed } from 'vue';
import {
    formatBdtCompact,
    formatKatha,
    formatMonth,
    formatNumber,
    formatSftRange,
} from '@/lib/format';
import type { ProjectCopy, ProjectDetail } from '@/types/project';

// PG-04 title block (LAY-PROJ-DETAIL): overline with the segment and area
// links (SEO-ONP-005), light H1, tagline, then a hairline facts strip —
// size, bedrooms, handover, price — so FR-PROJ-001's key facts sit in the
// first screen (2 × 2 on phones). Status and booking are the chips on the
// gallery photo. The price shows only when the server sent one (AC-PROJ-02).
const props = defineProps<{
    project: ProjectDetail;
    copy: ProjectCopy;
}>();

type Fact = { label: string; value: string; muted?: boolean };

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
    const list: Fact[] = [];

    if (p.size_min_sft !== null) {
        list.push({
            label: 'Size',
            value: formatSftRange(p.size_min_sft, p.size_max_sft),
        });
    } else if (p.land_katha !== null) {
        list.push({ label: 'Plot size', value: formatKatha(p.land_katha) });
    }

    const beds = range(p.beds_min, p.beds_max);

    if (beds !== null) {
        list.push({ label: 'Bedrooms', value: beds });
    }

    list.push(
        p.handover_date
            ? {
                  label:
                      p.lifecycle === 'handed_over'
                          ? 'Handed over'
                          : 'Handover',
                  value: formatMonth(p.handover_date),
              }
            : { label: 'Status', value: p.lifecycle_label },
    );

    list.push(
        p.price_from_bdt !== null
            ? {
                  label: props.copy.price_from_label,
                  value: formatBdtCompact(p.price_from_bdt),
              }
            : {
                  label: 'Price',
                  value: p.price_note ?? 'Price on request',
                  muted: true,
              },
    );

    return list;
});
</script>

<template>
    <div>
        <p
            class="flex flex-wrap items-center gap-x-2 gap-y-1 text-overline text-muted-foreground uppercase"
        >
            <Link
                v-if="project.segment_url"
                :href="project.segment_url"
                class="inline-flex min-h-11 items-center text-primary underline-offset-4 hover:underline"
            >
                {{ project.segment_label }}
            </Link>
            <span v-else>{{ project.segment_label }}</span>
            <span aria-hidden="true">·</span>
            <Link
                :href="project.location.url"
                class="inline-flex min-h-11 items-center gap-1 underline-offset-4 transition-colors duration-(--dur-fast) hover:text-primary hover:underline"
            >
                <MapPin class="size-3.5" aria-hidden="true" />
                {{ project.location.area }}, {{ project.location.city }}
            </Link>
        </p>

        <h1 class="mt-2 font-display text-h1 text-balance">
            {{ project.name }}
        </h1>
        <p
            v-if="project.tagline"
            class="mt-4 max-w-prose text-lead text-pretty text-muted-foreground"
        >
            {{ project.tagline }}
        </p>

        <dl
            :class="[
                'mt-8 grid grid-cols-2 border-y border-border lg:mt-10',
                facts.length === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-4',
            ]"
        >
            <div
                v-for="(fact, index) in facts"
                :key="fact.label"
                :class="[
                    'flex flex-col gap-2 border-border py-5 sm:py-6',
                    index > 0 && 'sm:border-l sm:pl-6 lg:pl-8',
                    index % 2 === 1 && 'max-sm:border-l max-sm:pl-4',
                    index > 1 && 'max-sm:border-t',
                ]"
            >
                <dt class="text-overline text-muted-foreground uppercase">
                    {{ fact.label }}
                </dt>
                <dd
                    :class="[
                        'tabular',
                        fact.muted
                            ? 'text-body text-muted-foreground'
                            : 'font-display text-fact',
                    ]"
                >
                    {{ fact.value }}
                </dd>
            </div>
        </dl>
    </div>
</template>
