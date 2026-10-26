<script setup lang="ts">
import { useListing } from '@/composables/useProjectListing';
import { cn } from '@/lib/utils';

// CMP-FILT-01 Bedrooms: minimum beds as a single-choice toggle row
// (1–5+, FR-SRCH-001 "bedrooms (min)"); pressing the chosen one clears it.
const props = defineProps<{
    idPrefix: string;
}>();

const listing = useListing();

function choose(value: number): void {
    listing.update({ beds: listing.state.beds === value ? null : value });
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <div role="group" aria-label="Minimum bedrooms" class="flex gap-2">
            <button
                v-for="option in listing.facets.value.beds"
                :key="option.value"
                type="button"
                :data-filter-key="`${props.idPrefix}:beds:${option.value}`"
                :aria-pressed="listing.state.beds === option.value"
                :aria-label="`${option.label} or more bedrooms, ${option.count} projects`"
                :disabled="
                    option.count === 0 && listing.state.beds !== option.value
                "
                :class="
                    cn(
                        'tabular inline-flex h-11 min-w-11 flex-1 items-center justify-center rounded-md border border-border px-3 text-body transition-colors duration-(--dur-fast) hover:not-disabled:border-foreground/40 disabled:cursor-not-allowed disabled:opacity-50',
                        listing.state.beds === option.value &&
                            'border-foreground bg-foreground text-background hover:not-disabled:border-foreground',
                    )
                "
                @click="choose(option.value)"
            >
                {{ option.label }}
            </button>
        </div>
        <p class="text-sm text-muted-foreground">
            Projects with at least this many bedrooms in one unit type.
        </p>
    </div>
</template>
