<script setup lang="ts">
import { Images } from '@lucide/vue';
import { computed } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useCarousel } from '@/composables/useCarousel';
import { formatNumber } from '@/lib/format';
import type { GalleryPhoto, ProjectDetail } from '@/types/project';

// CMP-GAL-01 detail hero gallery. From 768 px: one large photo (8 of 12
// columns, the LCP image) beside a 2 × 2 grid of tiles and "View all n
// photos" (Rancon). Below 768 px: an Embla strip, one 4:3 slide at a time,
// with a "1 / n" counter; a native scroll-snap strip before hydration.
// Every photo is a button that opens the lightbox at that photo; Embla is
// switched off from 768 px so the mosaic is plain grid layout.
const props = defineProps<{
    project: ProjectDetail;
    photos: GalleryPhoto[];
}>();

const emit = defineEmits<{
    open: [index: number];
}>();

const { viewport, api, selected } = useCarousel({
    slidesToScroll: 1,
    breakpoints: { '(min-width: 768px)': { active: false } },
});

const mosaic = computed(() => props.photos.length >= 5);
const total = computed(() => formatNumber(props.photos.length));

function itemClass(index: number): string {
    if (index === 0) {
        return mosaic.value
            ? 'md:col-span-8 md:row-span-2'
            : 'md:col-span-12 md:row-span-2';
    }

    return mosaic.value && index < 5 ? 'md:col-span-2' : 'md:hidden';
}

function label(photo: GalleryPhoto, index: number): string {
    const position = `Open photo ${index + 1} of ${props.photos.length}`;

    return photo.image.alt ? `${position}: ${photo.image.alt}` : position;
}
</script>

<template>
    <div class="relative">
        <div
            ref="viewport"
            :class="[
                'rounded-xl md:overflow-visible md:rounded-none',
                api
                    ? 'overflow-hidden'
                    : 'snap-x snap-mandatory overflow-x-auto',
            ]"
        >
            <ul
                :class="[
                    'flex md:grid md:grid-cols-12 md:grid-rows-2 md:gap-2',
                    mosaic ? 'md:aspect-[8/3]' : 'md:aspect-[12/5]',
                ]"
            >
                <li
                    v-for="(photo, index) in photos"
                    :key="photo.id"
                    :class="[
                        'min-w-0 shrink-0 grow-0 basis-full snap-start',
                        itemClass(index),
                    ]"
                >
                    <button
                        type="button"
                        class="media-card relative block size-full overflow-hidden outline-none focus-visible:outline-2 focus-visible:-outline-offset-4 focus-visible:outline-white md:rounded-xl"
                        :aria-label="label(photo, index)"
                        @click="emit('open', index)"
                    >
                        <ResponsiveImage
                            :image="photo.image"
                            :preset="
                                index === 0 ? 'gallery-hero' : 'gallery-tile'
                            "
                            :priority="index === 0"
                            img-class="media-zoom"
                            class="md:aspect-auto md:size-full"
                        />
                    </button>
                </li>
            </ul>
        </div>

        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-24 rounded-t-xl bg-linear-to-b from-stone-950/25 to-transparent md:right-auto md:w-2/3"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute top-4 left-4 flex flex-wrap gap-1.5"
        >
            <Badge
                variant="lifecycle"
                :value="project.lifecycle"
                on-media
                dot
                class="backdrop-blur-sm"
            >
                {{ project.lifecycle_label }}
            </Badge>
            <Badge
                v-if="project.booking_state !== 'none' && project.booking_label"
                variant="booking"
                :value="project.booking_state"
                on-media
                class="backdrop-blur-sm"
            >
                {{ project.booking_label }}
            </Badge>
        </div>

        <p
            class="tabular pointer-events-none absolute right-3 bottom-3 rounded-md bg-stone-950/55 px-2.5 py-1 text-sm text-white backdrop-blur-sm md:hidden"
            aria-hidden="true"
        >
            {{ formatNumber(selected + 1) }} / {{ total }}
        </p>

        <Button
            v-if="photos.length > 1"
            type="button"
            variant="light"
            class="absolute right-4 bottom-4 hidden md:inline-flex"
            @click="emit('open', 0)"
        >
            <Images aria-hidden="true" />
            View all {{ total }} photos
        </Button>
    </div>
</template>
