<script setup lang="ts">
import { computed } from 'vue';
import SearchCombobox from '@/components/site/search/SearchCombobox.vue';
import SearchSelect from '@/components/site/search/SearchSelect.vue';
import SegmentToggle from '@/components/site/search/SegmentToggle.vue';
import { fieldControlClass } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import type {
    ProjectSearchFilters,
    ProjectSearchOptions,
} from '@/types/search';

// HERO-040 fields. `bar`: the hero card, labels visually hidden, fields
// divided by 1 px lines — one row from 1024 px, two rows (2 + 3 fields)
// from 768 px, stacked below. `stacked`: the bottom sheet, visible labels.
const props = defineProps<{
    options: ProjectSearchOptions;
    layout: 'bar' | 'stacked';
    /** Unique per form on the page. */
    idPrefix: string;
    /** Popover layer: above the header, or above the sheet. */
    contentClass: string;
}>();

const filters = defineModel<ProjectSearchFilters>({ required: true });

const bar = computed(() => props.layout === 'bar');

const labelClass = computed(() =>
    bar.value ? 'sr-only' : 'mb-1.5 block text-sm font-medium',
);

const controlClass = computed(() =>
    bar.value
        ? cn(
              fieldControlClass,
              'flex h-12 items-center border-transparent bg-transparent shadow-none hover:not-disabled:border-transparent hover:not-disabled:bg-muted/70',
          )
        : cn(fieldControlClass, 'flex h-12 items-center'),
);

const segmentOptions = computed(() => [
    { value: '', label: 'All' },
    ...props.options.segments,
]);

const cityGroups = computed(() =>
    props.options.cities.map((city) => ({
        label: city.city,
        options: city.areas,
    })),
);

// Dividers between bar fields: md rows are [segment | location] and
// [status | size | beds], lg is one row.
const divider = 'md:border-l md:border-border md:pl-2';
</script>

<template>
    <div
        :class="
            cn(
                bar
                    ? 'flex flex-col gap-2 md:grid md:grid-cols-12 md:gap-y-2 lg:flex lg:flex-1 lg:flex-row lg:items-center lg:gap-2'
                    : 'flex flex-col gap-5',
            )
        "
    >
        <SegmentToggle
            v-model="filters.segment"
            name="segment"
            legend="Project type"
            :options="segmentOptions"
            :legend-class="labelClass"
            :class="bar && 'md:col-span-6 lg:shrink-0'"
        />
        <SearchCombobox
            :id="`${idPrefix}-area`"
            v-model="filters.area"
            name="area[]"
            label="Location"
            :placeholder="bar ? 'Location' : 'All locations'"
            any-label="All locations"
            empty-text="No matching area"
            :groups="cityGroups"
            :label-class="labelClass"
            :control-class="controlClass"
            :content-class="contentClass"
            :class="
                bar && cn(divider, 'md:col-span-6 lg:min-w-44 lg:flex-[1.4]')
            "
        />
        <SearchSelect
            :id="`${idPrefix}-status`"
            v-model="filters.status"
            name="status"
            label="Status"
            :placeholder="bar ? 'Status' : 'Any status'"
            any-label="Any status"
            :options="options.statuses"
            :label-class="labelClass"
            :control-class="controlClass"
            :content-class="contentClass"
            :class="
                bar &&
                'md:col-span-3 lg:flex-1 lg:border-l lg:border-border lg:pl-2'
            "
        />
        <SearchSelect
            :id="`${idPrefix}-size`"
            v-model="filters.size"
            name="size"
            label="Size"
            :placeholder="bar ? 'Size' : 'Any size'"
            any-label="Any size"
            :options="options.sizes"
            :label-class="labelClass"
            :control-class="controlClass"
            :content-class="contentClass"
            :class="bar && cn(divider, 'md:col-span-3 lg:flex-1')"
        />
        <SearchSelect
            :id="`${idPrefix}-beds`"
            v-model="filters.beds"
            name="beds"
            label="Bedrooms"
            :placeholder="bar ? 'Bedrooms' : 'Any bedrooms'"
            any-label="Any bedrooms"
            :options="options.beds"
            :label-class="labelClass"
            :control-class="controlClass"
            :content-class="contentClass"
            :class="bar && cn(divider, 'md:col-span-3 lg:flex-1')"
        />
        <slot />
    </div>
</template>
