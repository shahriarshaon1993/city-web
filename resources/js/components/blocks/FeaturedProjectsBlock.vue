<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import type { EmblaCarouselType } from 'embla-carousel';
import emblaCarouselVue from 'embla-carousel-vue';
import { computed, ref, watch } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ProjectGrid from '@/components/blocks/ProjectGrid.vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import { Button } from '@/components/ui/button';
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

const [viewport, api] = emblaCarouselVue({
    align: 'start',
    containScroll: 'trimSnaps',
    slidesToScroll: 'auto',
});

const canPrevious = ref(false);
const canNext = ref(false);

function update(embla: EmblaCarouselType): void {
    canPrevious.value = embla.canScrollPrev();
    canNext.value = embla.canScrollNext();
}

watch(api, (embla) => {
    if (embla) {
        update(embla);
        embla.on('select', update).on('reInit', update);
    }
});

// Respects reduced motion: Embla jumps instead of animating.
function scroll(direction: 'previous' | 'next'): void {
    const instant = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (direction === 'previous') {
        api.value?.scrollPrev(instant);
    } else {
        api.value?.scrollNext(instant);
    }
}
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
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="rounded-full"
                    aria-label="Previous projects"
                    :disabled="!canPrevious"
                    @click="scroll('previous')"
                >
                    <ChevronLeft class="size-5" aria-hidden="true" />
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    size="icon"
                    class="rounded-full"
                    aria-label="Next projects"
                    :disabled="!canNext"
                    @click="scroll('next')"
                >
                    <ChevronRight class="size-5" aria-hidden="true" />
                </Button>
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
            <ul class="-ml-4 flex py-2 md:-ml-6">
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
