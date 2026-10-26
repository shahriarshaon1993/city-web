<script setup lang="ts">
import { MapPinned } from '@lucide/vue';
import { computed } from 'vue';
import EmptyState from '@/components/site/EmptyState.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import SkeletonShape from '@/components/site/SkeletonShape.vue';
import { buttonVariants } from '@/components/ui/button';
import { useListing } from '@/composables/useProjectListing';
import { emptyFilters } from '@/lib/listing';
import { cn } from '@/lib/utils';
import type { ListingCopy } from '@/types/listing';
import type { ProjectCard as ProjectCardData } from '@/types/site';

// LAY-PROJ-LIST results. Grid: CMP-CARD-01, 1/2/3 columns, 48 px between
// rows; the first card is the page's LCP image (preloaded by the server).
// Map (`view=map`): horizontal cards in 5 columns beside the map's 7 — a
// placeholder panel until 5.1 brings the map. While a filter reload runs the
// results dim (aria-busy) and after 300 ms a skeleton covers them
// (UX-07, MOT-SKELETON). No results: AC-SRCH-04 empty state + newest 3.
// No scroll reveals here: the cards change under the visitor's hand.
const props = defineProps<{
    projects: ProjectCardData[];
    newest: ProjectCardData[];
    copy: ListingCopy;
}>();

const listing = useListing();

const map = computed(() => listing.state.view === 'map');
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
                <ul class="flex flex-col gap-8 lg:col-span-5">
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
                <div class="lg:col-span-7">
                    <div
                        class="flex min-h-80 flex-col items-center justify-center gap-4 rounded-xl bg-surface-subtle p-8 text-center lg:sticky lg:top-[calc(var(--header-height)+6rem)] lg:h-[calc(100svh-var(--header-height)-8rem)]"
                    >
                        <MapPinned
                            class="size-10 text-muted-foreground"
                            stroke-width="1.25"
                            aria-hidden="true"
                        />
                        <p class="font-display text-h4">
                            {{ props.copy.map_title }}
                        </p>
                        <p class="max-w-sm text-body text-muted-foreground">
                            {{ props.copy.map_body }}
                        </p>
                    </div>
                </div>
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
