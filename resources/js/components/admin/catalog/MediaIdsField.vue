<script setup lang="ts">
import { ImagePlus, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import MediaPicker from '@/components/admin/media/MediaPicker.vue';
import { Button } from '@/components/ui/button';
import { useCan } from '@/composables/useCan';
import type { MediaAsset } from '@/types';
import { useAssetMap } from './context';

// Several library images in order (CMS-MED-010 multi-item fields): progress photos, up to `max`.
// Thumbnails come from the editor's asset map; "Add photos" opens the picker in multiple mode.
const props = defineProps<{
    id: string;
    max: number;
    pickerTitle?: string;
    addLabel?: string;
    invalid?: boolean;
    describedby?: string;
}>();

const model = defineModel<number[]>({ required: true });
const { assets, remember } = useAssetMap();
const { canAny } = useCan();

const open = ref(false);
const canPick = computed(() =>
    canAny('media.view', 'media.upload', 'media.update'),
);
const room = computed(() => props.max - model.value.length);

function insert(picked: MediaAsset[]): void {
    for (const asset of picked) {
        remember(asset);
    }

    const next = [...model.value];

    for (const asset of picked) {
        if (!next.includes(asset.id) && next.length < props.max) {
            next.push(asset.id);
        }
    }

    model.value = next;
}

function remove(id: number): void {
    model.value = model.value.filter((item) => item !== id);
}
</script>

<template>
    <div
        :id="id"
        class="grid gap-3"
        role="group"
        :aria-describedby="describedby"
    >
        <ul
            v-if="model.length"
            class="grid grid-cols-3 gap-2 sm:grid-cols-4 lg:grid-cols-6"
            :class="invalid ? 'rounded-lg ring-1 ring-destructive' : ''"
        >
            <li
                v-for="photo in model"
                :key="photo"
                class="group relative aspect-4/3 overflow-hidden rounded-md bg-muted"
            >
                <img
                    v-if="assets[photo]?.thumb_url"
                    :src="assets[photo].thumb_url ?? ''"
                    :alt="assets[photo].alt ?? assets[photo].title"
                    class="size-full object-cover"
                />
                <span
                    v-else
                    class="grid size-full place-items-center text-xs text-muted-foreground"
                    >#{{ photo }}</span
                >
                <button
                    type="button"
                    class="absolute top-1 right-1 grid size-7 place-items-center rounded-full bg-black/60 text-white opacity-100 hover:bg-black/80 sm:opacity-0 sm:group-focus-within:opacity-100 sm:group-hover:opacity-100"
                    :aria-label="`Remove ${assets[photo]?.title ?? 'photo'}`"
                    @click="remove(photo)"
                >
                    <X class="size-4" aria-hidden="true" />
                </button>
            </li>
        </ul>

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
                {{ addLabel ?? 'Add photos' }}
            </Button>
            <p class="text-xs text-muted-foreground">
                {{ model.length }} of {{ max }}
                <template v-if="room <= 0"> · the limit is reached</template>
            </p>
        </div>

        <MediaPicker
            v-if="canPick"
            v-model:open="open"
            multiple
            :kinds="['image']"
            :title="pickerTitle ?? 'Choose photos'"
            insert-label="Add"
            @insert="insert"
        />
    </div>
</template>
