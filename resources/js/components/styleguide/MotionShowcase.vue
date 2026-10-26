<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import type { StyleguideImages } from '@/components/styleguide/types';
import { Button } from '@/components/ui/button';

// 3.2 interactive effects (design/motion.md §8.4): MOT-MAGNETIC, MOT-GLOW,
// MOT-TILT, MOT-PARALLAX, MOT-LINE. Tilt and glow are used nowhere else yet
// (owner, 3.2). All of them are inert under reduced motion and on touch
// (parallax is halved there).
defineProps<{
    images: StyleguideImages;
}>();

const steps = [
    'Choose a project',
    'Book a site visit',
    'Sign the allotment',
    'Move in',
];
</script>

<template>
    <StyleguideSection
        id="sg-motion"
        title="Motion"
        description="Pointer effects need a mouse; parallax and the line draw scrub with the scroll. Turn on “Reduce motion” in the footer to see the static versions."
    >
        <div class="grid gap-10 lg:grid-cols-2">
            <div class="flex flex-col gap-3">
                <h3 class="font-mono text-xs text-muted-foreground">
                    v-magnetic · max 6 px, label 1.3×
                </h3>
                <div
                    class="theme-dark flex flex-wrap items-center gap-3 rounded-xl bg-(image:--gradient-dusk) p-8"
                >
                    <Button v-magnetic variant="light" size="lg">
                        <span data-magnetic-label class="inline-flex">
                            Book a visit
                        </span>
                    </Button>
                    <Button v-magnetic variant="on-media" size="lg">
                        <span
                            data-magnetic-label
                            class="inline-flex items-center gap-2"
                        >
                            View projects
                            <ArrowRight aria-hidden="true" />
                        </span>
                    </Button>
                </div>

                <h3 class="mt-4 font-mono text-xs text-muted-foreground">
                    glow · rotating ring on hover / focus
                </h3>
                <div class="flex flex-wrap items-center gap-3 p-2">
                    <Button variant="glow" size="lg">Enquire now</Button>
                </div>
            </div>

            <div class="flex flex-col gap-3">
                <h3 class="font-mono text-xs text-muted-foreground">
                    v-tilt · max 6°, depth 20 px, glare 0.15
                </h3>
                <div v-tilt class="w-full max-w-xs">
                    <div
                        data-tilt-depth
                        class="relative overflow-hidden rounded-xl"
                    >
                        <ResponsiveImage
                            :image="images.project"
                            preset="project-card"
                        />
                        <span
                            data-tilt-glare
                            class="pointer-events-none absolute inset-0 bg-linear-to-br from-white to-transparent opacity-0"
                            aria-hidden="true"
                        />
                    </div>
                    <p class="mt-3 text-h4">Feature card</p>
                </div>
            </div>
        </div>

        <h3 class="font-mono text-xs text-muted-foreground">
            v-parallax · 10 % (5 % on touch), layer -inset-y-[10%]
        </h3>
        <div v-parallax="10" class="relative h-80 overflow-hidden rounded-xl">
            <div data-parallax-layer class="absolute inset-x-0 -inset-y-[10%]">
                <ResponsiveImage
                    :image="images.interior"
                    preset="cta-band"
                    class="absolute inset-0"
                />
            </div>
        </div>

        <h3 class="font-mono text-xs text-muted-foreground">
            v-line · drawn while the steps cross 70 % of the viewport
        </h3>
        <div v-line class="relative max-w-md py-2 pl-12">
            <svg
                class="absolute top-0 left-4 h-full w-px overflow-visible"
                aria-hidden="true"
            >
                <line
                    data-line-path
                    x1="0"
                    y1="0"
                    x2="0"
                    y2="100%"
                    class="stroke-primary"
                    stroke-width="2"
                />
            </svg>
            <ol class="flex flex-col gap-10">
                <li
                    v-for="(step, index) in steps"
                    :key="step"
                    class="flex flex-col gap-1"
                >
                    <span class="text-overline text-primary uppercase">
                        Step {{ index + 1 }}
                    </span>
                    <span class="text-h4">{{ step }}</span>
                </li>
            </ol>
        </div>
    </StyleguideSection>
</template>
