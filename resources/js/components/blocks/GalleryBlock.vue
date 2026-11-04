<script setup lang="ts">
import { computed } from 'vue';
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { useLightbox } from '@/composables/useLightbox';
import type { LightboxSlide } from '@/composables/useLightbox';
import type { GalleryBlock } from '@/types/blocks';

// CMP-BLK-GALLERY / CMP-GAL-01: `grid` = uniform 4:3 tiles (2 across, 3 from
// 1024 px), `masonry` = CSS columns that keep each photo's own ratio. Tiles
// are buttons ("Open photo n of N: alt") that open the shared PhotoSwipe
// lightbox, which loads on the first click only. Photos zoom slowly on hover
// like every media card (direction.md §7).
const props = defineProps<{
    block: GalleryBlock;
    first: boolean;
}>();

const lightbox = useLightbox();

const slides = computed<LightboxSlide[]>(() =>
    props.block.data.images.map((image) => ({ image })),
);

function label(index: number): string {
    const image = props.block.data.images[index];
    const position = `Open photo ${index + 1} of ${props.block.data.images.length}`;

    return image.alt ? `${position}: ${image.alt}` : position;
}

function show(index: number): void {
    void lightbox.open(
        slides.value,
        index,
        props.block.data.heading ?? 'Photo gallery',
    );
}
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        />
        <ul
            :class="
                block.data.layout === 'masonry'
                    ? 'columns-2 gap-3 sm:gap-4 lg:columns-3'
                    : 'grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3'
            "
        >
            <li
                v-for="(image, index) in block.data.images"
                :key="index"
                :class="
                    block.data.layout === 'masonry'
                        ? 'mb-3 break-inside-avoid sm:mb-4'
                        : undefined
                "
            >
                <button
                    type="button"
                    class="media-card block w-full overflow-hidden rounded-xl outline-none focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring focus-visible:outline-solid"
                    :aria-label="label(index)"
                    @click="show(index)"
                >
                    <ResponsiveImage
                        :image="image"
                        :preset="
                            block.data.layout === 'masonry'
                                ? 'gallery-block-masonry'
                                : 'gallery-block'
                        "
                        img-class="media-zoom"
                    />
                </button>
            </li>
        </ul>
    </BlockSection>
</template>
