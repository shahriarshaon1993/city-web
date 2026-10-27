<script setup lang="ts">
import { MapIcon } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import EmptyState from '@/components/site/EmptyState.vue';
import MapSheet from '@/components/site/map/MapSheet.vue';
import ProjectMap from '@/components/site/map/ProjectMap.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import SkeletonShape from '@/components/site/SkeletonShape.vue';
import { Button, buttonVariants } from '@/components/ui/button';
import { useListing } from '@/composables/useProjectListing';
import { formatNumber } from '@/lib/format';
import { emptyFilters } from '@/lib/listing';
import { cn } from '@/lib/utils';
import type { ListingCopy } from '@/types/listing';
import type { MapProject } from '@/types/location';
import type { ProjectCard as ProjectCardData } from '@/types/site';

// LAY-PROJ-LIST results. Grid: CMP-CARD-01, 1/2/3 columns, 48 px between
// rows; the first card is the page's LCP image (preloaded by the server).
// Map (`view=map`, FR-SRCH-006): horizontal cards in 5 columns beside the
// sticky map's 7 (CMP-MAP-01, every result a marker); below 1024 px the list
// stays and the map opens as a full-screen sheet — at once when the visitor
// switches to map view, else from "Show map". While a filter reload runs the
// results dim (aria-busy) and after 300 ms a skeleton covers them
// (UX-07, MOT-SKELETON). No results: AC-SRCH-04 empty state + newest 3.
// No scroll reveals here: the cards change under the visitor's hand.
const props = defineProps<{
    projects: ProjectCardData[];
    /** Every result with coordinates; null outside map view. */
    markers: MapProject[] | null;
    newest: ProjectCardData[];
    copy: ListingCopy;
}>();

const listing = useListing();

const map = computed(() => listing.state.view === 'map');
const sheetOpen = ref(false);
const sheetTitle = computed(() => {
    const total = listing.meta.value.total;

    return total === 1
        ? '1 project on the map'
        : `${formatNumber(total)} projects on the map`;
});

watch(map, (isMap, wasMap) => {
    if (isMap && !wasMap && window.matchMedia('(max-width: 1023px)').matches) {
        sheetOpen.value = true;
    }
});
const clearUrl = computed(() => listing.urlFor(emptyFilters()));

function clear(event: MouseEvent): void {
    if (
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.button !== 0
    ) {
        return;
    }

    event.preventDefault();
    listing.clearAll();
}
</script>

<template>
    <div
        class="listing-results relative"
        :aria-busy="listing.pending.value ? 'true' : 'false'"
    >
        <template v-if="props.projects.length > 0">
            <div v-if="map" class="grid gap-8 lg:grid-cols-12">
                <div class="flex flex-col gap-8 lg:col-span-5">
                    <Button
                        type="button"
                        variant="outline"
                        class="self-start lg:hidden"
                        @click="sheetOpen = true"
                    >
                        <MapIcon class="size-4" aria-hidden="true" />
                        Show map
                    </Button>
                    <ul class="flex flex-col gap-8">
                        <li
                            v-for="(project, index) in props.projects"
                            :key="project.id"
                        >
                            <ProjectCard
                                :project="project"
                                variant="horizontal"
                                heading-as="h2"
                                :priority="index === 0"
                            />
                        </li>
                    </ul>
                </div>
                <div class="hidden lg:col-span-7 lg:block">
                    <ProjectMap
                        :projects="props.markers ?? []"
                        label="Map of the projects found"
                        class="sticky top-[calc(var(--header-height)+6rem)] h-[calc(100svh-var(--header-height)-8rem)] min-h-140 rounded-xl"
                    />
                </div>
                <MapSheet
                    v-model:open="sheetOpen"
                    :title="sheetTitle"
                    :projects="props.markers ?? []"
                    label="Map of the projects found"
                />
            </div>

            <ul
                v-else
                class="grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8"
            >
                <li
                    v-for="(project, index) in props.projects"
                    :key="project.id"
                >
                    <ProjectCard
                        :project="project"
                        heading-as="h2"
                        :priority="index === 0"
                    />
                </li>
            </ul>
        </template>

        <div v-else class="flex flex-col gap-12">
            <EmptyState
                variant="no-results"
                :title="props.copy.empty_title"
                :body="props.copy.empty_body"
                title-as="h2"
            >
                <a
                    :href="clearUrl"
                    :class="buttonVariants({ variant: 'outline' })"
                    @click="clear"
                >
                    Clear filters
                </a>
            </EmptyState>

            <section
                v-if="props.newest.length > 0"
                aria-labelledby="listing-newest"
                class="flex flex-col gap-8"
            >
                <h2
                    id="listing-newest"
                    class="border-b border-border pb-5 font-display text-h3"
                >
                    {{ props.copy.newest_heading }}
                </h2>
                <ul
                    class="grid gap-x-6 gap-y-12 sm:grid-cols-2 lg:grid-cols-3 xl:gap-x-8"
                >
                    <li v-for="project in props.newest" :key="project.id">
                        <ProjectCard :project="project" heading-as="h3" />
                    </li>
                </ul>
            </section>
        </div>

        <div
            v-if="listing.skeleton.value"
            class="absolute inset-0 overflow-hidden bg-background/70"
            aria-hidden="true"
        >
            <div
                :class="
                    cn(
                        'grid gap-x-6 gap-y-12 xl:gap-x-8',
                        map ? 'lg:w-5/12' : 'sm:grid-cols-2 lg:grid-cols-3',
                    )
                "
            >
                <SkeletonShape v-for="n in 6" :key="n" shape="project-card" />
            </div>
        </div>
    </div>
</template>
