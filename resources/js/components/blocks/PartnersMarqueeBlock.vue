<script setup lang="ts">
import { Pause, Play } from '@lucide/vue';
import { ref, useTemplateRef } from 'vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { useOffscreen } from '@/composables/useOffscreen';
import type { PartnersMarqueeBlock } from '@/types/blocks';

// CMP-BLK-MARQUEE / CMP-MARQ-01 + MOT-MARQUEE: two copies of the logo row in
// a CSS track translating -50% (60 s slow / 40 s normal per loop), edge-to-
// edge with 80 px fade masks. The second copy is aria-hidden + inert, so
// assistive technology meets each partner once (AC-SOC-01). Pauses on hover,
// focus-within, off-screen and with the pause button (visible on focus).
// Reduced motion: no animation, one copy wrapped as a static grid (MOT-R03).
// Each copy is at least the viewport wide, so a short list still loops
// without a gap.
const props = defineProps<{
    block: PartnersMarqueeBlock;
    first: boolean;
}>();

const marquee = useTemplateRef<HTMLElement>('marquee');
const offscreen = useOffscreen(marquee);
const paused = ref(false);

const duration = props.block.data.speed === 'slow' ? '60s' : '40s';
</script>

<template>
    <BlockSection
        v-if="block.data.partners.length > 0"
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
        width="full"
        section-class="border-y border-border"
    >
        <!-- A quiet caption, not a display heading (DEC-48): the logos are the content. -->
        <component
            :is="headingTag"
            v-if="block.data.heading"
            :id="headingId"
            class="mx-auto mb-8 max-w-content px-4 text-center text-overline text-muted-foreground uppercase sm:px-6 lg:mb-10 lg:px-8"
        >
            {{ block.data.heading }}
        </component>

        <div
            ref="marquee"
            class="marquee relative"
            :style="{ '--marquee-duration': duration }"
            :data-direction="block.data.direction"
            :data-paused="paused ? '' : undefined"
            :data-offscreen="offscreen ? '' : undefined"
        >
            <button
                type="button"
                class="marquee-toggle absolute -top-2 right-4 z-(--z-raised) inline-flex size-11 items-center justify-center rounded-full bg-background text-foreground shadow-sm not-focus-visible:sr-only motion-reduce:hidden sm:right-6 lg:right-8"
                :aria-label="
                    paused ? 'Play partner logos' : 'Pause partner logos'
                "
                @click="paused = !paused"
            >
                <Play v-if="paused" class="size-5" aria-hidden="true" />
                <Pause v-else class="size-5" aria-hidden="true" />
            </button>

            <div class="marquee-viewport overflow-hidden">
                <div class="marquee-track flex w-max">
                    <ul
                        v-for="copy in 2"
                        :key="copy"
                        class="marquee-copy flex min-w-screen shrink-0 items-center justify-around gap-x-16 px-8 lg:gap-x-24 lg:px-12"
                        :aria-hidden="copy === 2 ? 'true' : undefined"
                        :inert="copy === 2"
                    >
                        <li
                            v-for="partner in block.data.partners"
                            :key="partner.id"
                            class="flex shrink-0 items-center py-2"
                        >
                            <!-- FR-SOC-002: partner links are nofollow. -->
                            <component
                                :is="partner.url ? 'a' : 'span'"
                                :href="partner.url ?? undefined"
                                :target="partner.url ? '_blank' : undefined"
                                :rel="
                                    partner.url
                                        ? 'noopener nofollow'
                                        : undefined
                                "
                                class="marquee-logo flex min-h-11 items-center rounded-sm"
                            >
                                <ResponsiveImage
                                    v-if="partner.logo"
                                    :image="partner.logo"
                                    preset="partner-logo"
                                    class="h-9 w-auto lg:h-11"
                                />
                                <span
                                    v-else
                                    class="text-lg font-medium whitespace-nowrap"
                                    >{{ partner.name }}</span
                                >
                            </component>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </BlockSection>
</template>
