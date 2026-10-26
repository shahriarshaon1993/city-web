<script setup lang="ts">
import { SlidersHorizontal } from '@lucide/vue';
import { computed, ref } from 'vue';
import BottomSheet from '@/components/site/BottomSheet.vue';
import AreaPanel from '@/components/site/listing/AreaPanel.vue';
import BedsPanel from '@/components/site/listing/BedsPanel.vue';
import FacetLinks from '@/components/site/listing/FacetLinks.vue';
import KeywordField from '@/components/site/listing/KeywordField.vue';
import MorePanel from '@/components/site/listing/MorePanel.vue';
import RangePanel from '@/components/site/listing/RangePanel.vue';
import {
    Accordion,
    AccordionContent,
    AccordionItem,
    AccordionTrigger,
} from '@/components/ui/accordion';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useListing } from '@/composables/useProjectListing';
import { formatBdtCompact, formatSft, formatNumber } from '@/lib/format';
import { priceLabel, sizeLabel } from '@/lib/listing';

// CMP-FILT-01 mobile (< 1024 px): "Filters (n)" opens a bottom sheet with
// stacked accordion sections. Changes apply live like on desktop, so the
// sticky "Show {count} projects" always carries the current count and only
// closes the sheet.
const listing = useListing();
const open = ref(false);

const meta = computed(() => listing.meta.value);
const facets = computed(() => listing.facets.value);

const areaSummary = computed(() => {
    const count = listing.state.area.length;

    return count === 0 ? null : `${count} selected`;
});

const showLabel = computed(() =>
    meta.value.total === 1
        ? 'Show 1 project'
        : `Show ${formatNumber(meta.value.total)} projects`,
);
</script>

<template>
    <BottomSheet v-model:open="open" title="Filters">
        <template #trigger>
            <Button
                type="button"
                variant="outline"
                class="h-11 gap-2"
                data-filter-key="sheet-trigger"
            >
                <SlidersHorizontal class="size-4" aria-hidden="true" />
                Filters
                <span
                    v-if="meta.active_count > 0"
                    class="tabular inline-flex min-w-6 items-center justify-center rounded-full bg-foreground px-1.5 text-sm text-background"
                >
                    {{ meta.active_count }}
                    <span class="sr-only">active</span>
                </span>
            </Button>
        </template>

        <div class="flex flex-col gap-2 pt-2">
            <KeywordField id-prefix="sheet" />

            <Accordion
                type="multiple"
                :default-value="['segment', 'status']"
                class="mt-2"
            >
                <AccordionItem value="segment">
                    <AccordionTrigger class="text-body-lg">
                        Project type
                    </AccordionTrigger>
                    <AccordionContent>
                        <FacetLinks
                            facet="segment"
                            label="Project type"
                            all-label="All"
                            :all-count="facets.all_count"
                            :options="facets.segments"
                            id-prefix="sheet"
                            layout="wrap"
                        />
                    </AccordionContent>
                </AccordionItem>
                <AccordionItem value="status">
                    <AccordionTrigger class="text-body-lg">
                        Status
                    </AccordionTrigger>
                    <AccordionContent>
                        <FacetLinks
                            facet="status"
                            label="Status"
                            all-label="Any"
                            :all-count="facets.any_status_count"
                            :options="facets.statuses"
                            id-prefix="sheet"
                            layout="wrap"
                        />
                    </AccordionContent>
                </AccordionItem>
                <AccordionItem value="area">
                    <AccordionTrigger class="text-body-lg">
                        <span class="flex flex-1 justify-between gap-3 pr-2">
                            Location
                            <span
                                v-if="areaSummary"
                                class="text-sm text-muted-foreground"
                            >
                                {{ areaSummary }}
                            </span>
                        </span>
                    </AccordionTrigger>
                    <AccordionContent>
                        <AreaPanel id-prefix="sheet" />
                    </AccordionContent>
                </AccordionItem>
                <AccordionItem value="size">
                    <AccordionTrigger class="text-body-lg">
                        <span class="flex flex-1 justify-between gap-3 pr-2">
                            Size
                            <span
                                v-if="sizeLabel(listing.state)"
                                class="text-sm text-muted-foreground"
                            >
                                {{ sizeLabel(listing.state) }}
                            </span>
                        </span>
                    </AccordionTrigger>
                    <AccordionContent>
                        <RangePanel
                            label="Size"
                            id-prefix="sheet-size"
                            :bounds="facets.size_bounds"
                            :min="listing.state.size_min"
                            :max="listing.state.size_max"
                            :format="formatSft"
                            :presets="facets.sizes"
                            class="px-1"
                            @change="
                                (min, max) =>
                                    listing.update({
                                        size_min: min,
                                        size_max: max,
                                    })
                            "
                        />
                    </AccordionContent>
                </AccordionItem>
                <AccordionItem value="beds">
                    <AccordionTrigger class="text-body-lg">
                        <span class="flex flex-1 justify-between gap-3 pr-2">
                            Bedrooms
                            <span
                                v-if="listing.state.beds"
                                class="text-sm text-muted-foreground"
                            >
                                {{ listing.state.beds }}+
                            </span>
                        </span>
                    </AccordionTrigger>
                    <AccordionContent>
                        <BedsPanel id-prefix="sheet" />
                    </AccordionContent>
                </AccordionItem>
                <AccordionItem v-if="facets.price_bounds" value="price">
                    <AccordionTrigger class="text-body-lg">
                        <span class="flex flex-1 justify-between gap-3 pr-2">
                            Price
                            <span
                                v-if="priceLabel(listing.state)"
                                class="text-sm text-muted-foreground"
                            >
                                {{ priceLabel(listing.state) }}
                            </span>
                        </span>
                    </AccordionTrigger>
                    <AccordionContent>
                        <RangePanel
                            label="Price"
                            id-prefix="sheet-price"
                            :bounds="facets.price_bounds"
                            :min="listing.state.price_min"
                            :max="listing.state.price_max"
                            :format="formatBdtCompact"
                            class="px-1"
                            @change="
                                (min, max) =>
                                    listing.update({
                                        price_min: min,
                                        price_max: max,
                                    })
                            "
                        />
                    </AccordionContent>
                </AccordionItem>
                <AccordionItem value="more">
                    <AccordionTrigger class="text-body-lg">
                        More filters
                    </AccordionTrigger>
                    <AccordionContent>
                        <MorePanel id-prefix="sheet" />
                    </AccordionContent>
                </AccordionItem>
            </Accordion>
        </div>

        <template #footer>
            <div class="flex items-center gap-3">
                <Button
                    type="button"
                    variant="ghost"
                    class="h-12"
                    :disabled="meta.active_count === 0"
                    @click="listing.clearAll()"
                >
                    Clear
                </Button>
                <Button
                    type="button"
                    variant="primary"
                    size="lg"
                    class="flex-1"
                    @click="open = false"
                >
                    <Spinner v-if="listing.pending.value" class="size-5" />
                    {{ showLabel }}
                </Button>
            </div>
        </template>
    </BottomSheet>
</template>
