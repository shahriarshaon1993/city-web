<script setup lang="ts">
import { computed, useTemplateRef } from 'vue';
import { useOffscreen } from '@/composables/useOffscreen';
import { cn } from '@/lib/utils';
import type { ProgressStage } from '@/types/site';

// CMP-PROG-01 4-stage segmented bar with the stage names under it. Stages
// up to the current one are filled; the current one pulses (MOT-PULSE,
// paused off-screen, off under reduced motion). The bar is the accessible
// value (`role="progressbar"`, value text names the stage), so the names
// below are aria-hidden. Used by ProgressSummary (home) and PG-04.
const props = defineProps<{
    percent: number;
    stage: ProgressStage;
    stageLabel: string;
    stages: { value: ProgressStage; label: string }[];
    label: string;
}>();

const current = computed(() =>
    props.stages.findIndex((stage) => stage.value === props.stage),
);
const complete = computed(() => props.stage === 'complete');

const bar = useTemplateRef<HTMLElement>('bar');
const offscreen = useOffscreen(bar);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div
            ref="bar"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-valuenow="percent"
            :aria-valuetext="`${percent}% complete, ${stageLabel} stage`"
            :aria-label="label"
            :data-offscreen="offscreen ? '' : undefined"
            class="grid grid-cols-4 gap-1"
        >
            <span
                v-for="(item, index) in stages"
                :key="item.value"
                :class="
                    cn(
                        'h-1 rounded-full',
                        index <= current ? 'bg-primary' : 'bg-foreground/10',
                        index === current && !complete && 'progress-pulse',
                    )
                "
            />
        </div>
        <ol class="grid grid-cols-4 gap-1 text-xs" aria-hidden="true">
            <li
                v-for="(item, index) in stages"
                :key="item.value"
                :class="
                    index === current
                        ? 'font-medium text-foreground'
                        : 'text-muted-foreground'
                "
            >
                {{ item.label }}
            </li>
        </ol>
    </div>
</template>
