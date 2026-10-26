<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { formatNumber } from '@/lib/format';
import type { GalleryCategory, GalleryPhoto } from '@/types/project';

// PG-04 Gallery section (LAY-PROJ-DETAIL "category tabs + masonry"): a row
// of category filters in the listing's underline style (DEC-53) above CSS
// masonry columns (2, 3 from 768 px). The filters appear after hydration;
// without JS every photo shows. Each photo opens the lightbox on the whole
// gallery at that photo, so its category buttons still work there.
const props = defineProps<{
    photos: GalleryPhoto[];
}>();

const emit = defineEmits<{
    open: [index: number];
}>();

const hydrated = ref(false);
const filter = ref<GalleryCategory | null>(null);

onMounted(() => {
    hydrated.value = true;
});

const categories = computed(() => {
    const counts = new Map<GalleryCategory, { label: string; count: number }>();

    for (const photo of props.photos) {
        const entry = counts.get(photo.category);

        if (entry) {
            entry.count++;
        } else {
            counts.set(photo.category, {
                label: photo.category_label,
                count: 1,
            });
        }
    }

    return [...counts].map(([value, entry]) => ({ value, ...entry }));
});

const options = computed(() => [
    { value: null, label: 'All', count: props.photos.length },
    ...categories.value,
]);

function label(photo: GalleryPhoto, index: number): string {
    const position = `Open photo ${index + 1} of ${props.photos.length}`;

    return photo.image.alt ? `${position}: ${photo.image.alt}` : position;
}
</script>

<template>
    <div>
        <div
            v-if="hydrated && categories.length > 1"
            role="group"
            aria-label="Filter photos by category"
            class="mb-6 flex flex-wrap gap-x-6 gap-y-1"
        >
            <button
                v-for="option in options"
                :key="option.value ?? 'all'"
                type="button"
                :aria-pressed="filter === option.value"
                class="relative inline-flex min-h-11 items-center gap-1.5 text-sm text-muted-foreground transition-colors duration-(--dur-fast) after:absolute after:inset-x-0 after:bottom-2 after:h-px after:origin-left after:scale-x-0 after:bg-foreground after:transition-transform after:duration-(--dur-base) hover:text-foreground aria-pressed:text-foreground aria-pressed:after:scale-x-100"
                @click="filter = option.value"
            >
                {{ option.label }}
                <span class="tabular text-muted-foreground">{{
                    formatNumber(option.count)
                }}</span>
            </button>
        </div>

        <ul class="columns-2 gap-3 sm:gap-4 md:columns-3">
            <li
                v-for="(photo, index) in photos"
                v-show="filter === null || photo.category === filter"
                :key="photo.id"
                class="mb-3 break-inside-avoid sm:mb-4"
            >
                <button
                    type="button"
                    class="media-card block w-full overflow-hidden rounded-xl"
                    :aria-label="label(photo, index)"
                    @click="emit('open', index)"
                >
                    <ResponsiveImage
                        :image="photo.image"
                        preset="gallery-masonry"
                        img-class="media-zoom"
                    />
                </button>
            </li>
        </ul>
    </div>
</template>
