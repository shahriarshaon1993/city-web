<script setup lang="ts">
import { computed } from 'vue';
import ProgressBar from '@/components/site/ProgressBar.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { useLightbox } from '@/composables/useLightbox';
import type { LightboxSlide } from '@/composables/useLightbox';
import { formatMonth } from '@/lib/format';
import type { ProgressUpdate } from '@/types/project';
import type { ProgressStage } from '@/types/site';

// CMP-PROG-01 on PG-04 (FR-PROJ-005): the latest update as a big light
// percentage (counts up, MOT-COUNTER; aria-hidden — the bar carries the
// value), "Updated {Month YYYY} · stage", the note and the 4-stage bar;
// below, every dated update as a hairline row (month · % · stage | note ·
// photo strip). Strip photos open the lightbox with one category per month.
const props = defineProps<{
    updates: ProgressUpdate[];
    stages: { value: ProgressStage; label: string }[];
    projectName: string;
    timelineHeading: string;
}>();

const lightbox = useLightbox();

const latest = computed(() => props.updates[0]);

const slides = computed<LightboxSlide[]>(() =>
    props.updates.flatMap((update) =>
        update.photos.map((image) => ({
            image,
            category: {
                value: String(update.id),
                label: formatMonth(update.reported_on),
            },
        })),
    ),
);

function openPhoto(updateIndex: number, photoIndex: number): void {
    const offset = props.updates
        .slice(0, updateIndex)
        .reduce((total, update) => total + update.photos.length, 0);

    void lightbox.open(
        slides.value,
        offset + photoIndex,
        `${props.projectName} construction photos`,
    );
}
</script>

<template>
    <div v-if="latest" class="flex flex-col gap-12">
        <div class="flex flex-col gap-8">
            <div class="grid gap-6 sm:grid-cols-12 sm:items-end">
                <p
                    class="font-display text-stat leading-none text-primary sm:col-span-5"
                    aria-hidden="true"
                >
                    <span
                        class="tabular inline-block"
                        :style="{
                            minWidth: `${String(latest.percent).length}ch`,
                        }"
                        v-counter
                        data-counter
                        :data-value="latest.percent"
                        data-decimals="0"
                        >{{ latest.percent }}</span
                    ><span class="ml-0.5 text-[0.5em]">%</span>
                </p>
                <div class="flex flex-col gap-3 sm:col-span-7">
                    <p class="text-overline text-muted-foreground uppercase">
                        Updated
                        <time :datetime="latest.reported_on.slice(0, 7)">{{
                            formatMonth(latest.reported_on)
                        }}</time>
                        ·
                        <span class="text-primary">{{
                            latest.stage_label
                        }}</span>
                    </p>
                    <p
                        v-if="latest.note"
                        class="max-w-prose text-body-lg text-pretty"
                    >
                        {{ latest.note }}
                    </p>
                </div>
            </div>
            <ProgressBar
                :percent="latest.percent"
                :stage="latest.stage"
                :stage-label="latest.stage_label"
                :stages="stages"
                :label="`Construction progress of ${projectName}`"
            />
        </div>

        <div>
            <h3 class="mb-4 font-display text-h4">{{ timelineHeading }}</h3>
            <ol class="divide-y divide-border border-y border-border">
                <li
                    v-for="(update, updateIndex) in updates"
                    :key="update.id"
                    class="grid gap-4 py-6 sm:grid-cols-12 sm:gap-6"
                >
                    <div
                        class="flex items-baseline gap-4 sm:col-span-3 sm:flex-col sm:gap-2"
                    >
                        <time
                            :datetime="update.reported_on.slice(0, 7)"
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ formatMonth(update.reported_on) }}
                        </time>
                        <p class="tabular font-display text-fact">
                            {{ update.percent }}%
                            <span
                                class="ml-1 font-sans text-sm font-normal text-muted-foreground"
                            >
                                {{ update.stage_label }}
                            </span>
                        </p>
                    </div>
                    <div class="flex min-w-0 flex-col gap-4 sm:col-span-9">
                        <p
                            v-if="update.note"
                            class="max-w-prose text-pretty text-muted-foreground"
                        >
                            {{ update.note }}
                        </p>
                        <ul
                            v-if="update.photos.length > 0"
                            class="flex [scrollbar-width:none] gap-2 overflow-x-auto"
                        >
                            <li
                                v-for="(photo, photoIndex) in update.photos"
                                :key="photoIndex"
                                class="shrink-0"
                            >
                                <button
                                    type="button"
                                    class="media-card block overflow-hidden rounded-lg"
                                    :aria-label="`Open photo ${photoIndex + 1} of ${update.photos.length}, ${formatMonth(update.reported_on)}`"
                                    @click="openPhoto(updateIndex, photoIndex)"
                                >
                                    <ResponsiveImage
                                        :image="photo"
                                        preset="gallery-thumb"
                                        class="size-20 sm:size-24"
                                        img-class="media-zoom"
                                    />
                                </button>
                            </li>
                        </ul>
                    </div>
                </li>
            </ol>
        </div>
    </div>
</template>
