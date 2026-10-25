<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import CarouselArrows from '@/components/blocks/CarouselArrows.vue';
import TestimonialCard from '@/components/site/TestimonialCard.vue';
import { useCarousel } from '@/composables/useCarousel';
import type { TestimonialsBlock } from '@/types/blocks';

// CMP-BLK-TESTIMONIALS: CMP-TEST-01 carousel or grid. The carousel shows one
// large quote at a time (DEC-48) with a "01 / 06" counter and the arrows in
// the heading row (DEC-47); no autoplay. Until Embla starts (and without
// JavaScript) the row is a scroll-snap strip.
const props = defineProps<{
    block: TestimonialsBlock;
    first: boolean;
}>();

const carousel = computed(() => props.block.data.layout === 'carousel');

const { viewport, api, canPrevious, canNext, selected, count, scroll } =
    useCarousel();

const pad = (value: number): string => String(value).padStart(2, '0');
</script>

<template>
    <BlockSection
        v-if="block.data.items.length > 0"
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        >
            <template
                v-if="carousel && api && (canPrevious || canNext)"
                #actions
            >
                <!-- Visual only: the slides themselves are all in the reading order. -->
                <span
                    class="tabular mr-3 text-sm text-muted-foreground"
                    aria-hidden="true"
                >
                    <span class="text-foreground">{{ pad(selected + 1) }}</span>
                    / {{ pad(count) }}
                </span>
                <CarouselArrows
                    :can-previous="canPrevious"
                    :can-next="canNext"
                    previous-label="Previous testimonials"
                    next-label="Next testimonials"
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
            <ul class="-ml-8 flex py-2">
                <li
                    v-for="testimonial in block.data.items"
                    :key="testimonial.id"
                    class="min-w-0 shrink-0 grow-0 basis-full snap-start pl-8"
                >
                    <TestimonialCard
                        :testimonial="testimonial"
                        variant="feature"
                        clamp
                    />
                </li>
            </ul>
        </div>

        <ul v-else class="grid gap-x-8 gap-y-12 md:grid-cols-2 lg:grid-cols-3">
            <li v-for="testimonial in block.data.items" :key="testimonial.id">
                <TestimonialCard :testimonial="testimonial" />
            </li>
        </ul>
    </BlockSection>
</template>
