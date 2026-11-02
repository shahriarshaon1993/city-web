<script setup lang="ts">
import BlockLinkButton from '@/components/blocks/BlockLinkButton.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { cn } from '@/lib/utils';
import type { ImageTextBlock } from '@/types/blocks';

// CMP-BLK-IMGTXT: photo in 6 columns, text in 5 with a one-column gutter
// (direction.md §5, split pattern); `image_side` swaps them from 1024 px, the
// image comes first on phones either way. The diagonal image mask (MOT-MASK)
// is optional per block (`reveal`); copy fades up (MOT-REVEAL). All of it is
// visible without the motion runtime.
defineProps<{
    block: ImageTextBlock;
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
            class="grid gap-10 lg:grid-cols-12 lg:items-center lg:gap-x-8 xl:gap-x-10"
        >
            <ResponsiveImage
                v-if="block.data.reveal === 'mask'"
                v-mask
                :image="block.data.image"
                preset="media-text"
                :class="
                    cn(
                        'rounded-xl lg:col-span-6 lg:row-start-1',
                        block.data.image_side === 'left'
                            ? 'lg:col-start-1'
                            : 'lg:col-start-7',
                    )
                "
            />
            <ResponsiveImage
                v-else
                :image="block.data.image"
                preset="media-text"
                :class="
                    cn(
                        'rounded-xl lg:col-span-6 lg:row-start-1',
                        block.data.image_side === 'left'
                            ? 'lg:col-start-1'
                            : 'lg:col-start-7',
                    )
                "
            />
            <div
                :class="
                    cn(
                        'flex flex-col gap-6 lg:col-span-5 lg:row-start-1',
                        block.data.image_side === 'left'
                            ? 'lg:col-start-8'
                            : 'lg:col-start-1',
                    )
                "
            >
                <component
                    :is="headingTag"
                    :id="headingId"
                    v-reveal
                    class="text-h2 text-balance"
                >
                    {{ block.data.heading }}
                </component>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <div
                    v-reveal="150"
                    class="rich-text text-muted-foreground"
                    v-html="block.data.body"
                />
                <BlockLinkButton
                    v-if="block.data.cta"
                    v-reveal="300"
                    :link="block.data.cta"
                    variant="outline"
                    class="self-start"
                />
            </div>
        </div>
    </BlockSection>
</template>
