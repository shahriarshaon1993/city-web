<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import { computed, useTemplateRef } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { useOffscreen } from '@/composables/useOffscreen';
import { formatMonth } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ProgressStage, ProgressSummary } from '@/types/site';

// CMP-PROG-01 summary as an editorial row (DEC-48): photo | project + note |
// big percentage, 4-stage segmented bar with stage names, "Updated {Month
// YYYY}". Stages up to the current one are filled; the current one pulses
// (MOT-PULSE, paused off-screen, off under reduced motion). The bar is the
// accessible value (`role="progressbar"`); the big number is aria-hidden
// because it counts up (MOT-COUNTER, `v-counter`). One link: the name.
const props = withDefaults(
    defineProps<{
        summary: ProgressSummary;
        stages: { value: ProgressStage; label: string }[];
        headingAs?: 'h2' | 'h3';
    }>(),
    { headingAs: 'h3' },
);

const current = computed(() =>
    props.stages.findIndex((stage) => stage.value === props.summary.stage),
);
const complete = computed(() => props.summary.stage === 'complete');

const bar = useTemplateRef<HTMLElement>('bar');
const offscreen = useOffscreen(bar);
</script>

<template>
    <article
        class="media-card group/card relative grid gap-6 rounded-xl has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-4 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid md:grid-cols-12 md:items-center md:gap-8"
    >
        <ResponsiveImage
            :image="summary.photo"
            preset="progress-card"
            class="rounded-xl md:col-span-5 lg:col-span-4"
            img-class="media-zoom"
        />

        <div
            class="grid gap-8 md:col-span-7 lg:col-span-8 lg:grid-cols-8 lg:items-center"
        >
            <div class="flex flex-col gap-3 lg:col-span-4 lg:pr-4">
                <p
                    class="flex items-center gap-1.5 text-overline text-muted-foreground uppercase"
                >
                    <MapPin class="size-3.5 shrink-0" aria-hidden="true" />
                    {{ summary.project.location.area }},
                    {{ summary.project.location.city }}
                </p>
                <component :is="headingAs" class="text-h3 text-balance">
                    <Link
                        :href="summary.project.url"
                        class="transition-colors duration-(--dur-fast) outline-none group-hover/card:text-primary after:absolute after:inset-0 after:rounded-xl after:content-['']"
                    >
                        {{ summary.project.name }}
                    </Link>
                </component>
                <p
                    v-if="summary.note"
                    class="line-clamp-3 text-muted-foreground"
                >
                    {{ summary.note }}
                </p>
            </div>

            <div class="flex flex-col gap-4 lg:col-span-4">
                <div class="flex items-end justify-between gap-4">
                    <p
                        class="font-display text-stat leading-none text-primary"
                        aria-hidden="true"
                    >
                        <span
                            class="tabular inline-block"
                            :style="{
                                minWidth: `${String(summary.percent).length}ch`,
                            }"
                            v-counter
                            data-counter
                            :data-value="summary.percent"
                            data-decimals="0"
                            >{{ summary.percent }}</span
                        ><span class="ml-0.5 text-[0.5em]">%</span>
                    </p>
                    <p
                        class="pb-1 text-overline text-muted-foreground uppercase"
                    >
                        Updated
                        <time :datetime="summary.reported_on.slice(0, 7)">{{
                            formatMonth(summary.reported_on)
                        }}</time>
                    </p>
                </div>

                <div
                    ref="bar"
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    :aria-valuenow="summary.percent"
                    :aria-valuetext="`${summary.percent}% complete, ${summary.stage_label} stage`"
                    :aria-label="`Construction progress of ${summary.project.name}`"
                    :data-offscreen="offscreen ? '' : undefined"
                    class="grid grid-cols-4 gap-1"
                >
                    <span
                        v-for="(stage, index) in stages"
                        :key="stage.value"
                        :class="
                            cn(
                                'h-1 rounded-full',
                                index <= current
                                    ? 'bg-primary'
                                    : 'bg-foreground/10',
                                index === current &&
                                    !complete &&
                                    'progress-pulse',
                            )
                        "
                    />
                </div>
                <!-- Stage names; the bar's aria-valuetext already says them. -->
                <ol class="grid grid-cols-4 gap-1 text-xs" aria-hidden="true">
                    <li
                        v-for="(stage, index) in stages"
                        :key="stage.value"
                        :class="
                            index === current
                                ? 'font-medium text-foreground'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ stage.label }}
                    </li>
                </ol>
            </div>
        </div>
    </article>
</template>
