<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed } from 'vue';
import { useListing } from '@/composables/useProjectListing';
import { activeChips } from '@/lib/listing';

// CMP-FILT-01 active filters row: one removable chip per value (buttons
// "Remove filter: Uttara") + "Clear all". Neutral hairline chips, never
// coloured (direction.md §8).
const listing = useListing();

const chips = computed(() => activeChips(listing.state, listing.facets.value));
</script>

<template>
    <div v-if="chips.length > 0" class="flex flex-wrap items-center gap-2">
        <button
            v-for="chip in chips"
            :key="chip.key"
            type="button"
            :data-filter-key="`chip:${chip.key}`"
            class="inline-flex min-h-11 items-center gap-1.5 rounded-md border border-border pr-2.5 pl-3 text-sm transition-colors duration-(--dur-fast) hover:border-foreground/40"
            @click="listing.update(chip.remove)"
        >
            <span class="sr-only">Remove filter:</span>
            {{ chip.label }}
            <X class="size-3.5 text-muted-foreground" aria-hidden="true" />
        </button>
        <button
            type="button"
            data-filter-key="chip:clear"
            class="inline-flex min-h-11 items-center px-2 text-sm font-medium text-primary underline-offset-4 hover:underline"
            @click="listing.clearAll()"
        >
            Clear all
        </button>
    </div>
</template>
