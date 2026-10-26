<script setup lang="ts">
import { ArrowDownUp } from '@lucide/vue';
import { computed } from 'vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useListing } from '@/composables/useProjectListing';
import type { ListingSort } from '@/types/listing';

// FR-SRCH-001 sort; price sorts are absent while the price filter is off
// (FR-SRCH-005). A non-default sort is noindex (SEO-TEC-021), so it is a
// control, never a link.
const props = defineProps<{
    idPrefix: string;
}>();

const listing = useListing();

const current = computed(
    () =>
        listing.facets.value.sorts.find(
            (sort) => sort.value === listing.state.sort,
        )?.label ?? '',
);

function choose(value: unknown): void {
    if (typeof value === 'string' && value !== listing.state.sort) {
        listing.update({ sort: value as ListingSort });
    }
}
</script>

<template>
    <div class="flex items-center">
        <label :for="`${props.idPrefix}-sort`" class="sr-only"> Sort by </label>
        <Select :model-value="listing.state.sort" @update:model-value="choose">
            <SelectTrigger
                :id="`${props.idPrefix}-sort`"
                :data-filter-key="`${props.idPrefix}-sort`"
                class="h-11 gap-2 rounded-md border-border px-3.5 text-sm shadow-none"
            >
                <ArrowDownUp
                    class="size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <!-- Icon-only below 640 px; the name stays for AT. -->
                <SelectValue class="max-sm:sr-only">{{ current }}</SelectValue>
            </SelectTrigger>
            <SelectContent
                data-lenis-prevent
                align="end"
                class="z-(--z-dropdown)"
            >
                <SelectItem
                    v-for="sort in listing.facets.value.sorts"
                    :key="sort.value"
                    :value="sort.value"
                >
                    {{ sort.label }}
                </SelectItem>
            </SelectContent>
        </Select>
    </div>
</template>
