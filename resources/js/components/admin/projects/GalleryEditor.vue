<script setup lang="ts">
import { ChevronLeft, ChevronRight, ImagePlus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import { newKey, useAssetMap } from '@/components/admin/catalog/context';
import MediaPicker from '@/components/admin/media/MediaPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCan } from '@/composables/useCan';
import type { GalleryItemForm, MediaAsset } from '@/types';
import { fid, moveItem, useProjectEditor } from './context';

// The project gallery (FR-PROJ, max 60): library images in order, each with a category (exterior,
// interior, amenity, site, night) and an optional caption. The alt text lives in the library and
// is checked on publish. Images are at least 1200 px wide; the picker adds several at once.
const { form, options, errors } = useProjectEditor();
const { assets, remember } = useAssetMap();
const { canAny } = useCan();

const open = ref(false);
const canPick = computed(() =>
    canAny('media.view', 'media.upload', 'media.update'),
);
const room = computed(() => options.limits.gallery - form.gallery.length);

function insert(picked: MediaAsset[]): void {
    for (const asset of picked) {
        remember(asset);

        if (
            form.gallery.length < options.limits.gallery &&
            !form.gallery.some((item) => item.asset_id === asset.id)
        ) {
            form.gallery.push({
                key: newKey('g'),
                asset_id: asset.id,
                category: 'exterior',
                caption: '',
            });
        }
    }
}

function itemError(index: number): string | undefined {
    return (
        errors[`gallery.${index}.asset_id`] ??
        errors[`gallery.${index}.category`] ??
        errors[`gallery.${index}.caption`]
    );
}

function move(item: GalleryItemForm, delta: -1 | 1): void {
    const index = form.gallery.findIndex((row) => row.key === item.key);
    form.gallery = moveItem(form.gallery, index, delta);
}
</script>

<template>
    <div id="field-gallery" class="grid gap-4">
        <p v-if="errors.gallery" class="text-sm text-destructive">
            {{ errors.gallery }}
        </p>

        <ul
            v-if="form.gallery.length"
            class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3"
        >
            <li
                v-for="(item, index) in form.gallery"
                :key="item.key"
                :class="[
                    'grid gap-2 rounded-lg border bg-card p-2',
                    itemError(index) ? 'border-destructive/60' : '',
                ]"
            >
                <div
                    class="relative aspect-4/3 overflow-hidden rounded-md bg-muted"
                >
                    <img
                        v-if="assets[item.asset_id]?.thumb_url"
                        :src="assets[item.asset_id].thumb_url ?? ''"
                        :alt="
                            assets[item.asset_id].alt ??
                            assets[item.asset_id].title
                        "
                        class="size-full object-cover"
                    />
                    <span
                        v-if="assets[item.asset_id]?.missing_alt"
                        class="absolute top-1 left-1 rounded bg-warning px-1.5 py-0.5 text-[11px] font-medium text-white"
                        >No alt text</span
                    >
                    <span
                        class="absolute bottom-1 left-1 rounded bg-black/60 px-1.5 py-0.5 text-[11px] text-white tabular-nums"
                        >{{ index + 1 }}</span
                    >
                    <button
                        type="button"
                        class="absolute top-1 right-1 grid size-7 place-items-center rounded-full bg-black/60 text-white hover:bg-black/80"
                        :aria-label="`Remove image ${index + 1}`"
                        @click="form.gallery.splice(index, 1)"
                    >
                        <X class="size-4" aria-hidden="true" />
                    </button>
                </div>

                <div class="flex items-center gap-1">
                    <div class="min-w-0 flex-1">
                        <AdminSelect
                            :id="fid('gallery', index, 'category')"
                            v-model="item.category"
                            :options="options.gallery_categories"
                            :invalid="!!errors[`gallery.${index}.category`]"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :disabled="index === 0"
                        :aria-label="`Move image ${index + 1} earlier`"
                        @click="move(item, -1)"
                    >
                        <ChevronLeft aria-hidden="true" />
                    </Button>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon-sm"
                        :disabled="index === form.gallery.length - 1"
                        :aria-label="`Move image ${index + 1} later`"
                        @click="move(item, 1)"
                    >
                        <ChevronRight aria-hidden="true" />
                    </Button>
                </div>
                <Input
                    :id="fid('gallery', index, 'caption')"
                    v-model="item.caption"
                    maxlength="200"
                    placeholder="Caption (optional)"
                    :aria-label="`Caption of image ${index + 1}`"
                />
                <p v-if="itemError(index)" class="text-xs text-destructive">
                    {{ itemError(index) }}
                </p>
            </li>
        </ul>
        <p v-else class="text-sm text-muted-foreground">
            No images yet. The project needs at least 4 to publish.
        </p>

        <div class="flex flex-wrap items-center gap-3">
            <Button
                v-if="canPick"
                type="button"
                variant="outline"
                size="sm"
                :disabled="room <= 0"
                @click="open = true"
            >
                <ImagePlus aria-hidden="true" />
                Add images
            </Button>
            <p class="text-xs text-muted-foreground">
                {{ form.gallery.length }} of {{ options.limits.gallery }} · at
                least 1200 px wide
            </p>
        </div>

        <MediaPicker
            v-if="canPick"
            v-model:open="open"
            multiple
            :kinds="['image']"
            title="Add gallery images"
            insert-label="Add to gallery"
            @insert="insert"
        />
    </div>
</template>
