<script setup lang="ts">
import { useTemplateRef } from 'vue';
import BlockLinkButton from '@/components/blocks/BlockLinkButton.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { useOffscreen } from '@/composables/useOffscreen';
import type { CtaBandBlock } from '@/types/blocks';

// CMP-BLK-CTA / CMP-CTA-01: full-bleed dark band, min 320 px, centred text
// (owner kept this over the split layout tried in DEC-48). Styles: `brand` (static --gradient-brand), `gradient` (--gradient-mesh
// drifting, MOT-GRAD: paused off-screen, static under reduced motion) and
// `image` (photo + 55 % stone-950 overlay). CTAs are `light` + `on-media`
// (DEC-47): the header's Enquire stays the one coral button (DP-04); both
// are magnetic (MOT-MAGNETIC, fine pointers only). The
// triangle cluster (DP-03) sits at the right edge from 1024 px. The layers
// are positioned against the <section> (the wrappers are static).
defineProps<{
    block: CtaBandBlock;
    first: boolean;
}>();

const band = useTemplateRef<HTMLElement>('band');
const offscreen = useOffscreen(band);
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        section-class="theme-dark relative isolate flex min-h-80 flex-col justify-center overflow-hidden"
    >
        <div ref="band" :data-offscreen="offscreen ? '' : undefined">
            <div
                v-if="block.data.style === 'brand'"
                class="absolute inset-0 -z-10 bg-(image:--gradient-brand)"
                aria-hidden="true"
            />
            <div
                v-else-if="block.data.style === 'gradient'"
                class="cta-mesh absolute -inset-1/2 -z-10"
                aria-hidden="true"
            />
            <div v-else class="absolute inset-0 -z-10">
                <ResponsiveImage
                    :image="block.data.image"
                    preset="cta-band"
                    class="absolute inset-0"
                />
                <div
                    class="absolute inset-0 bg-[rgb(16_12_11/0.55)]"
                    aria-hidden="true"
                />
            </div>

            <svg
                class="absolute top-1/2 right-0 -z-10 hidden w-56 translate-x-1/4 -translate-y-1/2 lg:block xl:w-72"
                viewBox="0 0 240 208"
                aria-hidden="true"
            >
                <path class="fill-primary/30" d="M160 0 120 69.3h80z" />
                <path class="fill-foreground/8" d="M120 69.3 80 138.6h80z" />
                <path class="fill-brand-accent" d="M120 69.3h80l-40 69.3z" />
                <path class="fill-primary/20" d="M200 69.3 160 138.6h80z" />
                <path class="fill-primary/15" d="M80 138.6 40 207.9h80z" />
                <path class="fill-primary/40" d="M80 138.6h80l-40 69.3z" />
                <path class="fill-foreground/8" d="M160 138.6 120 207.9h80z" />
                <path class="fill-primary/25" d="M160 138.6h80l-40 69.3z" />
            </svg>

            <div
                v-reveal
                class="mx-auto flex max-w-2xl flex-col items-center gap-6 text-center"
            >
                <component
                    :is="headingTag"
                    :id="headingId"
                    class="text-h2 text-balance"
                >
                    {{ block.data.heading }}
                </component>
                <p
                    v-if="block.data.text"
                    class="text-body-lg text-pretty text-foreground/80"
                >
                    {{ block.data.text }}
                </p>
                <div class="mt-2 flex flex-wrap justify-center gap-3">
                    <BlockLinkButton
                        v-magnetic
                        :link="block.data.primary_cta"
                        variant="light"
                        size="lg"
                    >
                        <span data-magnetic-label class="inline-flex">
                            {{ block.data.primary_cta.label }}
                        </span>
                    </BlockLinkButton>
                    <BlockLinkButton
                        v-if="block.data.secondary_cta"
                        v-magnetic
                        :link="block.data.secondary_cta"
                        variant="on-media"
                        size="lg"
                    >
                        <span data-magnetic-label class="inline-flex">
                            {{ block.data.secondary_cta.label }}
                        </span>
                    </BlockLinkButton>
                </div>
            </div>
        </div>
    </BlockSection>
</template>
