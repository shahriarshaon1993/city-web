<script setup lang="ts">
import { LayoutGrid, MapIcon } from '@lucide/vue';
import { useListing } from '@/composables/useProjectListing';
import { cn } from '@/lib/utils';
import type { ListingView } from '@/types/listing';

// FR-SRCH-006 Grid/Map toggle; `view=map` is in the URL and noindex
// (SEO-TEC-021), so these are buttons.
const props = defineProps<{
    idPrefix: string;
}>();

const listing = useListing();

const views: { value: ListingView; label: string; icon: typeof LayoutGrid }[] =
    [
        { value: 'grid', label: 'Grid view', icon: LayoutGrid },
        { value: 'map', label: 'Map view', icon: MapIcon },
    ];

function choose(view: ListingView): void {
    if (view !== listing.state.view) {
        listing.update({ view });
    }
}
</script>

<template>
    <div
        role="group"
        aria-label="Results view"
        class="flex rounded-md border border-border"
    >
        <button
            v-for="view in views"
            :key="view.value"
            type="button"
            :data-filter-key="`${props.idPrefix}-view-${view.value}`"
            :aria-pressed="listing.state.view === view.value"
            :aria-label="view.label"
            :class="
                cn(
                    'flex size-11 items-center justify-center rounded-sm text-muted-foreground transition-colors duration-(--dur-fast) hover:text-foreground',
                    listing.state.view === view.value &&
                        'bg-muted text-foreground',
                )
            "
            @click="choose(view.value)"
        >
            <component :is="view.icon" class="size-4" aria-hidden="true" />
        </button>
    </div>
</template>
