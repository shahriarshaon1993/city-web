<script setup lang="ts">
import { SliderRange, SliderRoot, SliderThumb, SliderTrack } from 'reka-ui';
import { computed, ref, watch } from 'vue';
import { cn } from '@/lib/utils';
import type { RangeBounds, SizeBandFacet } from '@/types/listing';

// CMP-FILT-01 Size / Price: dual-thumb slider (keyboard: arrows, Page
// Up/Down, Home/End) plus optional presets (Settings → Search size bands).
// The values apply on release (`valueCommit`), so dragging is one history
// entry. A thumb at its bound means "no limit" (null).
const props = defineProps<{
    label: string;
    bounds: RangeBounds;
    min: number | null;
    max: number | null;
    format: (value: number) => string;
    presets?: SizeBandFacet[];
    /** Unique per instance, for the preset focus keys. */
    idPrefix: string;
}>();

const emit = defineEmits<{
    change: [min: number | null, max: number | null];
}>();

const draft = ref<number[]>([]);

watch(
    () => [props.min, props.max, props.bounds] as const,
    () => {
        draft.value = [
            props.min ?? props.bounds.min,
            props.max ?? props.bounds.max,
        ];
    },
    { immediate: true },
);

function commit(values: number[] | undefined): void {
    if (!values) {
        return;
    }

    const [low, high] = values;
    emit(
        'change',
        low <= props.bounds.min ? null : low,
        high >= props.bounds.max ? null : high,
    );
}

const summary = computed(() => {
    const [low, high] = draft.value;

    if (low <= props.bounds.min && high >= props.bounds.max) {
        return 'Any';
    }

    if (high >= props.bounds.max) {
        return `${props.format(low)}+`;
    }

    if (low <= props.bounds.min) {
        return `Up to ${props.format(high)}`;
    }

    return `${props.format(low)} – ${props.format(high)}`;
});

function presetActive(preset: SizeBandFacet): boolean {
    return props.min === preset.min && props.max === preset.max;
}

function choosePreset(preset: SizeBandFacet): void {
    if (presetActive(preset)) {
        emit('change', null, null);
    } else {
        emit('change', preset.min, preset.max);
    }
}

const thumbClass =
    'block size-6 rounded-full border-2 border-primary bg-background shadow-sm transition-[box-shadow] duration-(--dur-fast) after:absolute after:-inset-2.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring';
</script>

<template>
    <div class="flex flex-col gap-5">
        <p class="flex items-baseline justify-between gap-4">
            <span class="sr-only">{{ label }}:</span>
            <span class="tabular text-fact" aria-live="polite">
                {{ summary }}
            </span>
        </p>
        <SliderRoot
            v-model="draft"
            :min="bounds.min"
            :max="bounds.max"
            :step="bounds.step"
            :min-steps-between-thumbs="1"
            class="relative flex h-6 w-full touch-none items-center select-none"
            @value-commit="commit"
        >
            <SliderTrack class="relative h-1 grow rounded-full bg-border">
                <SliderRange class="absolute h-full rounded-full bg-primary" />
            </SliderTrack>
            <SliderThumb
                :class="thumbClass"
                :aria-label="`Minimum ${label.toLowerCase()}`"
                :aria-valuetext="format(draft[0] ?? bounds.min)"
            />
            <SliderThumb
                :class="thumbClass"
                :aria-label="`Maximum ${label.toLowerCase()}`"
                :aria-valuetext="format(draft[1] ?? bounds.max)"
            />
        </SliderRoot>
        <p
            class="tabular flex justify-between text-sm text-muted-foreground"
            aria-hidden="true"
        >
            <span>{{ format(bounds.min) }}</span>
            <span>{{ format(bounds.max) }}+</span>
        </p>

        <div v-if="presets?.length" class="flex flex-wrap gap-2">
            <button
                v-for="preset in presets"
                :key="preset.value"
                type="button"
                :data-filter-key="`${idPrefix}:preset:${preset.value}`"
                :aria-pressed="presetActive(preset)"
                :disabled="preset.count === 0 && !presetActive(preset)"
                :class="
                    cn(
                        'inline-flex min-h-11 items-center gap-1.5 rounded-md border border-border px-3 text-sm transition-colors duration-(--dur-fast) hover:not-disabled:border-foreground/40 disabled:cursor-not-allowed disabled:opacity-50',
                        presetActive(preset) &&
                            'border-foreground bg-foreground text-background hover:not-disabled:border-foreground',
                    )
                "
                @click="choosePreset(preset)"
            >
                {{ preset.label }}
                <span class="tabular opacity-70">{{ preset.count }}</span>
            </button>
        </div>
    </div>
</template>
