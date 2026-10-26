<script setup lang="ts">
import { computed } from 'vue';
import AreaPanel from '@/components/site/listing/AreaPanel.vue';
import BedsPanel from '@/components/site/listing/BedsPanel.vue';
import FilterPopover from '@/components/site/listing/FilterPopover.vue';
import FilterSheet from '@/components/site/listing/FilterSheet.vue';
import KeywordField from '@/components/site/listing/KeywordField.vue';
import MorePanel from '@/components/site/listing/MorePanel.vue';
import RangePanel from '@/components/site/listing/RangePanel.vue';
import SortSelect from '@/components/site/listing/SortSelect.vue';
import ViewToggle from '@/components/site/listing/ViewToggle.vue';
import { useListing } from '@/composables/useProjectListing';
import { formatBdtCompact, formatNumber, formatSft } from '@/lib/format';
import { priceLabel, sizeLabel } from '@/lib/listing';

// CMP-FILT-01 bar, sticky under the header (and up with it while headroom
// hides it). ≥ 1024 px: keyword, Location, Size, Bedrooms, Price (only with
// FR-SRCH-005), More || count, sort, Grid/Map. Below: "Filters (n)" sheet,
// sort and the view toggle. The visible count is aria-hidden: the page's
// live region announces it (CMP-FILT-01 A11y).
const listing = useListing();
const facets = computed(() => listing.facets.value);

const areaSummary = computed(() => {
    const areas = facets.value.cities.flatMap((city) => city.areas);
    const chosen = listing.state.area.map(
        (value) => areas.find((area) => area.value === value)?.label ?? value,
    );

    if (chosen.length === 0) {
        return null;
    }

    return chosen.length === 1 ? chosen[0] : `${chosen.length} areas`;
});

const moreSummary = computed(() => {
    const count =
        (listing.state.booking ? 1 : 0) + (listing.state.featured ? 1 : 0);

    return count === 0 ? null : String(count);
});

const countLabel = computed(() => {
    const total = listing.meta.value.total;

    return total === 1 ? '1 project' : `${formatNumber(total)} projects`;
});
</script>

<template>
    <div
        class="sticky-under-header sticky z-(--z-sticky) border-y border-border bg-background/95 backdrop-blur-md"
    >
        <div
            class="mx-auto flex min-h-16 max-w-content items-center gap-2 px-4 py-2.5 sm:px-6 lg:px-8 xl:px-10"
        >
            <!-- ≥ 1024 px -->
            <div class="hidden min-w-0 flex-1 items-center gap-2 lg:flex">
                <KeywordField id-prefix="bar" class="w-44 shrink xl:w-60" />
                <FilterPopover label="Location" :value="areaSummary">
                    <AreaPanel id-prefix="bar" />
                </FilterPopover>
                <FilterPopover label="Size" :value="sizeLabel(listing.state)">
                    <RangePanel
                        label="Size"
                        id-prefix="bar-size"
                        :bounds="facets.size_bounds"
                        :min="listing.state.size_min"
                        :max="listing.state.size_max"
                        :format="formatSft"
                        :presets="facets.sizes"
                        @change="
                            (min, max) =>
                                listing.update({ size_min: min, size_max: max })
                        "
                    />
                </FilterPopover>
                <FilterPopover
                    label="Bedrooms"
                    :value="
                        listing.state.beds ? `${listing.state.beds}+` : null
                    "
                >
                    <BedsPanel id-prefix="bar" />
                </FilterPopover>
                <FilterPopover
                    v-if="facets.price_bounds"
                    label="Price"
                    :value="priceLabel(listing.state)"
                >
                    <RangePanel
                        label="Price"
                        id-prefix="bar-price"
                        :bounds="facets.price_bounds"
                        :min="listing.state.price_min"
                        :max="listing.state.price_max"
                        :format="formatBdtCompact"
                        @change="
                            (min, max) =>
                                listing.update({
                                    price_min: min,
                                    price_max: max,
                                })
                        "
                    />
                </FilterPopover>
                <FilterPopover label="More" :value="moreSummary">
                    <MorePanel id-prefix="bar" />
                </FilterPopover>
            </div>

            <!-- < 1024 px -->
            <div class="flex min-w-0 flex-1 items-center gap-2 lg:hidden">
                <FilterSheet />
            </div>

            <p
                class="tabular hidden shrink-0 px-2 text-sm text-muted-foreground xl:block"
                aria-hidden="true"
            >
                {{ countLabel }}
            </p>
            <SortSelect id-prefix="bar" />
            <ViewToggle id-prefix="bar" />
        </div>
    </div>
</template>
