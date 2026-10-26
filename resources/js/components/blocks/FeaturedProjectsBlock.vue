<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import CarouselArrows from '@/components/blocks/CarouselArrows.vue';
import ProjectGrid from '@/components/blocks/ProjectGrid.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import { useCarousel } from '@/composables/useCarousel';
import type { FeaturedProjectsBlock } from '@/types/blocks';

// CMP-BLK-FEATURED: CMP-CARD-01 carousel (1.15 / 2.2 / 3 per view) or grid,
// heading row with "View all" and, for the carousel, prev/next arrows
// (DEC-44: no dots). Until Embla starts (and without JavaScript) the row is
// a native scroll-snap strip, so every card stays reachable.
const props = defineProps<{
    block: FeaturedProjectsBlock;
    first: boolean;
}>();

const carousel = computed(() => props.block.data.layout === 'carousel');

const { viewport, api, canPrevious, canNext, scroll } = useCarousel();
</script>

<template>
    <BlockSection
        v-if="block.data.projects.length > 0"
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
            :view-all="block.data.view_all"
        >
            <template
                v-if="carousel && api && (canPrevious || canNext)"
                #actions
            >
                <CarouselArrows
                    :can-previous="canPrevious"
                    :can-next="canNext"
                    previous-label="Previous projects"
                    next-label="Next projects"
                    @scroll="scroll"
                />
            </template>
        </BlockHeader>

        <div
            v-if="carousel"
            ref="viewport"
            :class="
                api
                    ? 'overflow-hidden'
                    : 'snap-x snap-mandatory overflow-x-auto pb-2'
            "
        >
            <!-- Vertical padding keeps the hover lift and focus ring inside the viewport. -->
            <ul v-reveal.stagger class="-ml-4 flex py-2 md:-ml-6">
                <li
                    v-for="project in block.data.projects"
                    :key="project.id"
                    class="min-w-0 shrink-0 grow-0 basis-[87%] snap-start pl-4 md:basis-[45.45%] md:pl-6 lg:basis-1/3"
                >
                    <!-- Inset ring: the viewport clips anything outside the card. -->
                    <ProjectCard
                        :project="project"
                        class="has-[a:focus-visible]:-outline-offset-2"
                    />
                </li>
            </ul>
        </div>

        <ProjectGrid v-else :projects="block.data.projects" />
    </BlockSection>
</template>
