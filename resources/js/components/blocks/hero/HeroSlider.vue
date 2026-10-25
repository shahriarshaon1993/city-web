<script setup lang="ts">
import { ChevronLeft, ChevronRight, Pause, Play } from '@lucide/vue';
import HeroShell from '@/components/blocks/hero/HeroShell.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { Button } from '@/components/ui/button';
import { useHeroSlider } from '@/composables/useHeroSlider';
import { cn } from '@/lib/utils';
import type { HeroBlockData } from '@/types/blocks';

// CMP-HERO-03: 2–5 image slides, crossfade (MOT-SLIDE), Ken Burns on the
// current slide while autoplay runs (off under reduced motion). Only slide 1
// is eager and preloaded (HERO-004); the rest load lazily after `load`.
// Without JavaScript slide 1 shows on its own.
const props = defineProps<{
    data: HeroBlockData;
    first: boolean;
}>();

const slider = useHeroSlider({
    count: () => props.data.slides.length,
    autoplay: () => props.data.autoplay,
});

const {
    viewport,
    selected,
    playing,
    running,
    preloadRest,
    goTo,
    next,
    previous,
    togglePlaying,
    rootListeners,
} = slider;
</script>

<template>
    <HeroShell
        :data="data"
        :first="first"
        aria-roledescription="carousel"
        aria-label="Hero images"
        v-on="rootListeners"
    >
        <template #media>
            <div ref="viewport" class="absolute inset-0 overflow-hidden">
                <div
                    class="flex h-full"
                    :aria-live="running ? 'off' : 'polite'"
                >
                    <div
                        v-for="(slide, index) in data.slides"
                        :key="index"
                        role="group"
                        aria-roledescription="slide"
                        :aria-label="`${index + 1} of ${data.slides.length}`"
                        :aria-hidden="index === selected ? undefined : 'true'"
                        class="relative h-full min-w-0 flex-[0_0_100%] overflow-hidden"
                    >
                        <ResponsiveImage
                            :image="slide.image"
                            :mobile="slide.mobile_image"
                            preset="hero"
                            :priority="first && index === 0"
                            :loading="
                                index === 0 || preloadRest ? 'eager' : 'lazy'
                            "
                            :class="
                                cn(
                                    'absolute inset-0',
                                    index === selected &&
                                        playing &&
                                        'hero-kenburns',
                                )
                            "
                            :style="{
                                animationPlayState: running
                                    ? 'running'
                                    : 'paused',
                            }"
                        />
                    </div>
                </div>
            </div>
        </template>

        <template #controls>
            <div
                class="flex items-center gap-1"
                @keydown.left.prevent="previous"
                @keydown.right.prevent="next"
            >
                <Button
                    v-if="data.autoplay"
                    type="button"
                    variant="on-media"
                    size="icon"
                    :aria-label="playing ? 'Pause slideshow' : 'Play slideshow'"
                    @click="togglePlaying"
                >
                    <Pause v-if="playing" class="size-5" aria-hidden="true" />
                    <Play v-else class="size-5" aria-hidden="true" />
                </Button>
                <Button
                    type="button"
                    variant="on-media"
                    size="icon"
                    aria-label="Previous slide"
                    @click="previous"
                >
                    <ChevronLeft class="size-5" aria-hidden="true" />
                </Button>
                <ul class="flex items-center">
                    <li v-for="(_, index) in data.slides" :key="index">
                        <button
                            type="button"
                            class="group flex size-11 items-center justify-center rounded-full"
                            :aria-label="`Slide ${index + 1} of ${data.slides.length}`"
                            :aria-current="
                                index === selected ? 'true' : undefined
                            "
                            @click="goTo(index)"
                        >
                            <span
                                :class="
                                    cn(
                                        'block size-2 rounded-full bg-white/50 transition-[transform,background-color] duration-(--dur-base) group-hover:bg-white/80',
                                        index === selected &&
                                            'scale-150 bg-white',
                                    )
                                "
                            />
                        </button>
                    </li>
                </ul>
                <Button
                    type="button"
                    variant="on-media"
                    size="icon"
                    aria-label="Next slide"
                    @click="next"
                >
                    <ChevronRight class="size-5" aria-hidden="true" />
                </Button>
            </div>
        </template>
    </HeroShell>
</template>
