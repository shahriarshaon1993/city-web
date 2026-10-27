<script setup lang="ts">
import { computed, onMounted, provide, ref, toRef } from 'vue';
import CtaBandBlock from '@/components/blocks/CtaBandBlock.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import ActiveChips from '@/components/site/listing/ActiveChips.vue';
import FacetLinks from '@/components/site/listing/FacetLinks.vue';
import FilterBar from '@/components/site/listing/FilterBar.vue';
import ListingFaq from '@/components/site/listing/ListingFaq.vue';
import ListingPagination from '@/components/site/listing/ListingPagination.vue';
import ListingResults from '@/components/site/listing/ListingResults.vue';
import LocationLinks from '@/components/site/listing/LocationLinks.vue';
import {
    projectListingKey,
    useProjectListing,
} from '@/composables/useProjectListing';
import { formatNumber } from '@/lib/format';
import type { ProjectListingProps } from '@/types/listing';

// PG-02 `/projects` and PG-03 facet pages (LAY-PROJ-LIST): breadcrumb, light
// H1 + intro (≤ 2 lines + Read more), the type/status link rows, the sticky
// filter bar, chips, results, pagination, location links, FAQ, CTA band.
// Filter changes reload `projects`, `facets`, `meta` (+ `listing`, `seo`)
// only (FR-SRCH-003); the count is announced politely (CMP-FILT-01).
const props = defineProps<ProjectListingProps>();

provide(
    projectListingKey,
    useProjectListing({
        meta: toRef(props, 'meta'),
        facets: toRef(props, 'facets'),
    }),
);

const announcement = computed(() => {
    const { total, page, last_page } = props.meta;
    const found =
        total === 1
            ? '1 project found'
            : `${formatNumber(total)} projects found`;

    return last_page > 1 ? `${found}, page ${page} of ${last_page}` : found;
});

const countLine = computed(() => {
    const { total, from, to } = props.meta;

    if (total === 0) {
        return null;
    }

    return total > to - from + 1
        ? `${formatNumber(from)}–${formatNumber(to)} of ${formatNumber(total)} projects`
        : total === 1
          ? '1 project'
          : `${formatNumber(total)} projects`;
});

// The intro is clamped only after hydration, so without JS it reads in full.
const hydrated = ref(false);
const introOpen = ref(false);
const introLong = computed(() => (props.listing.intro?.length ?? 0) > 150);

onMounted(() => {
    hydrated.value = true;
});
</script>

<template>
    <div>
        <section
            class="mx-auto max-w-content px-4 pt-8 sm:px-6 lg:px-8 lg:pt-12 xl:px-10"
        >
            <Breadcrumbs :items="listing.breadcrumbs" />

            <div
                class="mt-8 grid gap-6 lg:mt-12 lg:grid-cols-12 lg:items-end xl:gap-8"
            >
                <h1 class="font-display text-h1 text-balance lg:col-span-7">
                    {{ listing.title }}
                </h1>
                <div v-if="listing.intro" class="lg:col-span-5">
                    <p
                        id="listing-intro"
                        :class="[
                            'max-w-prose text-body-lg text-pretty text-muted-foreground',
                            hydrated &&
                                introLong &&
                                !introOpen &&
                                'line-clamp-2',
                        ]"
                    >
                        {{ listing.intro }}
                    </p>
                    <button
                        v-if="hydrated && introLong"
                        type="button"
                        class="mt-1 inline-flex min-h-11 items-center text-sm font-medium text-primary underline-offset-4 hover:underline"
                        aria-controls="listing-intro"
                        :aria-expanded="introOpen"
                        @click="introOpen = !introOpen"
                    >
                        {{ introOpen ? 'Read less' : 'Read more' }}
                    </button>
                </div>
            </div>

            <div
                class="mt-10 flex flex-col gap-3 lg:mt-14 lg:flex-row lg:items-center lg:justify-between lg:gap-10"
            >
                <FacetLinks
                    facet="segment"
                    label="Type"
                    all-label="All"
                    :all-count="facets.all_count"
                    :options="facets.segments"
                />
                <FacetLinks
                    facet="status"
                    label="Status"
                    all-label="Any"
                    :all-count="facets.any_status_count"
                    :options="facets.statuses"
                />
            </div>
        </section>

        <div class="mt-6">
            <FilterBar />

            <section
                id="results"
                aria-label="Projects"
                class="mx-auto flex max-w-content flex-col gap-8 px-4 pt-8 pb-(--section-md) sm:px-6 lg:px-8 lg:pt-10 xl:px-10"
            >
                <p class="sr-only" aria-live="polite" aria-atomic="true">
                    {{ announcement }}
                </p>
                <div
                    v-if="countLine || meta.active_count > 0"
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <ActiveChips />
                    <p
                        v-if="countLine"
                        class="tabular shrink-0 text-sm text-muted-foreground sm:ml-auto"
                        aria-hidden="true"
                    >
                        {{ countLine }}
                    </p>
                </div>

                <ListingResults
                    :projects="projects"
                    :markers="map"
                    :newest="newest"
                    :copy="copy"
                />
                <ListingPagination />
            </section>
        </div>

        <LocationLinks
            :heading="copy.locations_heading"
            :links="listing.location_links"
        />
        <ListingFaq :heading="copy.faq_heading" :items="listing.faq" />
        <CtaBandBlock :block="cta" :first="false" />
    </div>
</template>
