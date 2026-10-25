<script setup lang="ts">
import BlockLinkButton from '@/components/blocks/BlockLinkButton.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { IntroStatementBlock } from '@/types/blocks';

// CMP-BLK-INTRO: large statement (text-h2 at 600, ≤ 22ch per line) in 7
// columns, side image in 5 (below the text on mobile, LAY-HOME). The split-line
// reveal (MOT-SPLIT, `data-split`) and image mask (MOT-MASK, `data-mask`)
// arrive with the motion runtime in 3.1; everything is visible without them.
defineProps<{
    block: IntroStatementBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
    >
        <div
            class="grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-6 xl:gap-8"
        >
            <div class="flex flex-col gap-6 lg:col-span-7 lg:pr-8">
                <p
                    v-if="block.data.eyebrow"
                    class="text-overline text-primary uppercase"
                >
                    {{ block.data.eyebrow }}
                </p>
                <component
                    :is="headingTag"
                    :id="headingId"
                    data-split
                    class="max-w-[22ch] text-h2 text-balance"
                >
                    {{ block.data.heading }}
                </component>
                <!-- Sanitised server-side (rich text, CMS-PB-003). -->
                <div
                    v-if="block.data.body"
                    class="flex max-w-prose flex-col gap-4 text-body-lg text-muted-foreground"
                    v-html="block.data.body"
                />
                <BlockLinkButton
                    v-if="block.data.cta"
                    :link="block.data.cta"
                    variant="outline"
                    class="self-start"
                />
            </div>
            <ResponsiveImage
                v-if="block.data.image"
                data-mask
                :image="block.data.image"
                preset="intro-image"
                class="rounded-xl lg:col-span-5"
            />
        </div>
    </BlockSection>
</template>
