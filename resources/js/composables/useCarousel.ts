import type { EmblaCarouselType, EmblaOptionsType } from 'embla-carousel';
import emblaCarouselVue from 'embla-carousel-vue';
import { ref, watch } from 'vue';
import { isMotionReduced } from '@/motion/preference';

// Block carousels (featured projects, testimonials): Embla plus the state of
// the prev/next arrows in the heading row (DEC-44: no dots). Embla starts on
// mount, so SSR and no-JS visitors keep the native scroll-snap strip.
export function useCarousel(options: EmblaOptionsType = {}) {
    const [viewport, api] = emblaCarouselVue({
        align: 'start',
        containScroll: 'trimSnaps',
        slidesToScroll: 'auto',
        ...options,
    });

    const canPrevious = ref(false);
    const canNext = ref(false);
    /** Current snap and number of snaps (slide counters). */
    const selected = ref(0);
    const count = ref(0);

    function update(embla: EmblaCarouselType): void {
        canPrevious.value = embla.canScrollPrev();
        canNext.value = embla.canScrollNext();
        selected.value = embla.selectedScrollSnap();
        count.value = embla.scrollSnapList().length;
    }

    watch(api, (embla) => {
        if (embla) {
            update(embla);
            embla.on('select', update).on('reInit', update);
        }
    });

    // Respects reduced motion (MOT-R03): Embla jumps instead of animating.
    function scroll(direction: 'previous' | 'next'): void {
        const instant = isMotionReduced();

        if (direction === 'previous') {
            api.value?.scrollPrev(instant);
        } else {
            api.value?.scrollNext(instant);
        }
    }

    return { viewport, api, canPrevious, canNext, selected, count, scroll };
}
