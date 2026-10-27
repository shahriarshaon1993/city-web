<script setup lang="ts">
import { MapIcon } from '@lucide/vue';
import { computed, provide, ref, toRef } from 'vue';
import CtaBandBlock from '@/components/blocks/CtaBandBlock.vue';
import EmptyState from '@/components/site/EmptyState.vue';
import ListingFaq from '@/components/site/listing/ListingFaq.vue';
import ListingPagination from '@/components/site/listing/ListingPagination.vue';
import LocationLinks from '@/components/site/listing/LocationLinks.vue';
import AreaIntro from '@/components/site/location/AreaIntro.vue';
import AreaSegmentLinks from '@/components/site/location/AreaSegmentLinks.vue';
import LocationHero from '@/components/site/location/LocationHero.vue';
import MapSheet from '@/components/site/map/MapSheet.vue';
import ProjectMap from '@/components/site/map/ProjectMap.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import { Button } from '@/components/ui/button';
import {
    projectListingKey,
    useProjectListing,
} from '@/composables/useProjectListing';
import { formatNumber } from '@/lib/format';
import type { LocationPageProps } from '@/types/location';

// PG-06 `/locations/{area}` and `/locations/{area}/{segment}`
// (LAY-LOC-DETAIL): area hero (H1), intro 7 + facts 4, projects on greige
// with the segment links and real pagination (the listing composable with
// an area facet, so `?page=n` is a partial reload), the area map
// (CMP-MAP-01, 420 px, loads when scrolled into view; a full-screen sheet
// behind "Show map" below 768 px), nearby areas as hairline rows on greige,
// FAQ, CTA band. An area without projects shows the empty state and the
// newest projects elsewhere (AC-LOC-01).
const props = defineProps<LocationPageProps>();

const listing = useProjectListing({
    meta: toRef(props, 'meta'),
    facets: toRef(props, 'facets'),
});
provide(projectListingKey, listing);

const sheetOpen = ref(false);
const mapLabel = computed(() => `Map of projects in ${props.area.name}`);
const mapCount = computed(() => {
    const count = props.map.projects.length;

    return count === 1 ? '1 project' : `${formatNumber(count)} projects`;
});
</script>

<template>
    <div>
        <LocationHero :area="area" />
        <AreaIntro :area="area" :facts-heading="copy.facts_heading" />

        <section
            id="projects"
            aria-labelledby="area-projects"
            class="bg-surface-subtle py-(--section-md)"
        >
            <div
                class="mx-auto flex max-w-content flex-col gap-10 px-4 sm:px-6 lg:px-8 xl:px-10"
            >
                <div
                    class="flex flex-col gap-4 border-b border-border pb-5 lg:flex-row lg:items-end lg:justify-between lg:gap-10"
                >
                    <h2
                        id="area-projects"
                        class="font-display text-h2 text-balance"
                    >
                        {{ copy.projects_heading }}
                    </h2>
                    <AreaSegmentLinks :area="area" />
                </div>

                <template v-if="projects.length > 0">
                    <div
                        class="listing-results relative"
                        :aria-busy="listing.pending.value ? 'true' : 'false'"
                    >
                        <ul
                            class="grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8"
                        >
                            <li v-for="project in projects" :key="project.id">
                                <ProjectCard
                                    :project="project"
                                    heading-as="h3"
                                />
                            </li>
                        </ul>
                    </div>
                    <ListingPagination />
                </template>

                <div v-else class="flex flex-col gap-12">
                    <EmptyState
                        :title="copy.empty_title"
                        :body="copy.empty_body"
                        title-as="h3"
                    />
                    <div v-if="newest.length > 0" class="flex flex-col gap-8">
                        <p
                            class="border-b border-border pb-5 text-overline text-muted-foreground uppercase"
                        >
                            {{ copy.newest_heading }}
                        </p>
                        <ul
                            class="grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8"
                        >
                            <li v-for="project in newest" :key="project.id">
                                <ProjectCard
                                    :project="project"
                                    heading-as="h4"
                                />
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section
            v-if="map.projects.length > 0"
            aria-labelledby="area-map"
            class="py-(--section-md)"
        >
            <div
                class="mx-auto flex max-w-content flex-col gap-8 px-4 sm:px-6 lg:px-8 xl:px-10"
            >
                <div
                    class="flex flex-wrap items-end justify-between gap-4 border-b border-border pb-5"
                >
                    <h2 id="area-map" class="font-display text-h2 text-balance">
                        {{ copy.map_heading }}
                    </h2>
                    <p class="text-overline text-muted-foreground uppercase">
                        {{ mapCount }}
                    </p>
                </div>

                <ProjectMap
                    :projects="map.projects"
                    :centre="map.centre"
                    :zoom="map.zoom"
                    :label="mapLabel"
                    class="hidden h-105 rounded-xl md:block"
                />
                <div class="md:hidden">
                    <Button
                        type="button"
                        variant="outline"
                        @click="sheetOpen = true"
                    >
                        <MapIcon class="size-4" aria-hidden="true" />
                        Show map
                    </Button>
                    <MapSheet
                        v-model:open="sheetOpen"
                        :title="area.name"
                        :projects="map.projects"
                        :centre="map.centre"
                        :zoom="map.zoom"
                        :label="mapLabel"
                    />
                </div>
            </div>
        </section>

        <LocationLinks :heading="copy.nearby_heading" :links="area.nearby" />
        <ListingFaq :heading="copy.faq_heading" :items="area.faq" />
        <CtaBandBlock :block="cta" :first="false" />
    </div>
</template>
