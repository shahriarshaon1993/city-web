<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MapPin } from '@lucide/vue';
import ProgressBar from '@/components/site/ProgressBar.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { formatMonth } from '@/lib/format';
import type { ProgressStage, ProgressSummary } from '@/types/site';

// CMP-PROG-01 summary as an editorial row (DEC-48): photo | project + note |
// big percentage, 4-stage segmented bar with stage names (ProgressBar),
// "Updated {Month YYYY}". The bar is the accessible value; the big number
// is aria-hidden because it counts up (MOT-COUNTER, `v-counter`). One
// link: the name.
withDefaults(
    defineProps<{
        summary: ProgressSummary;
        stages: { value: ProgressStage; label: string }[];
        headingAs?: 'h2' | 'h3';
    }>(),
    { headingAs: 'h3' },
);
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

                <ProgressBar
                    :percent="summary.percent"
                    :stage="summary.stage"
                    :stage-label="summary.stage_label"
                    :stages="stages"
                    :label="`Construction progress of ${summary.project.name}`"
                />
            </div>
        </div>
    </article>
</template>
