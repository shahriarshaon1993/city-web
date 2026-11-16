<script setup lang="ts">
import { FileText, Film, ImagePlus } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useCan } from '@/composables/useCan';
import type { MediaAsset, MediaKind } from '@/types';
import MediaPicker from './MediaPicker.vue';

// CMS-MED-010: one media field (single item). Shows the chosen file with Change / Remove, or a
// "Choose" button; both open the picker. The form stores the id (`v-model`); the preview item is
// kept in `v-model:asset`. Multi-item fields (galleries) arrive with their editors (13.2, 16.x).
const props = withDefaults(
    defineProps<{
        id: string;
        kinds?: MediaKind[];
        chooseLabel?: string;
        pickerTitle?: string;
        invalid?: boolean;
        describedby?: string;
    }>(),
    {
        kinds: undefined,
        chooseLabel: 'Choose file',
        pickerTitle: undefined,
        invalid: false,
        describedby: undefined,
    },
);

const model = defineModel<number | null>({ required: true });
const asset = defineModel<MediaAsset | null>('asset', { default: null });

const { canAny } = useCan();
const canPick = computed(() =>
    canAny('media.view', 'media.upload', 'media.update'),
);
const pickerOpen = ref(false);

function onInsert(assets: MediaAsset[]): void {
    const picked = assets[0] ?? null;

    asset.value = picked;
    model.value = picked?.id ?? null;
}

function remove(): void {
    asset.value = null;
    model.value = null;
}

const meta = computed(() =>
    asset.value
        ? [
              asset.value.width && asset.value.height
                  ? `${asset.value.width} × ${asset.value.height}`
                  : null,
              asset.value.size_label,
          ]
              .filter(Boolean)
              .join(' · ')
        : '',
);
</script>

<template>
    <div
        :id="id"
        class="flex items-center gap-4 rounded-lg border p-3"
        :class="invalid ? 'border-destructive' : ''"
        role="group"
        :aria-describedby="describedby"
    >
        <div
            class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-md bg-muted text-muted-foreground"
            :style="
                asset?.dominant_color
                    ? { backgroundColor: asset.dominant_color }
                    : undefined
            "
        >
            <img
                v-if="asset?.thumb_url"
                :src="asset.thumb_url"
                :alt="asset.alt ?? ''"
                class="size-full object-cover"
                :style="{
                    objectPosition: `${asset.focal.x}% ${asset.focal.y}%`,
                }"
            />
            <Film
                v-else-if="asset?.kind === 'video'"
                class="size-6"
                aria-hidden="true"
            />
            <FileText v-else-if="asset" class="size-6" aria-hidden="true" />
            <ImagePlus v-else class="size-6" aria-hidden="true" />
        </div>

        <div class="grid min-w-0 flex-1 gap-2">
            <div v-if="asset" class="min-w-0">
                <p class="truncate text-sm font-medium">{{ asset.title }}</p>
                <p class="truncate text-xs text-muted-foreground tabular-nums">
                    {{ meta }}
                </p>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Nothing chosen yet.
            </p>

            <div v-if="canPick" class="flex flex-wrap gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="pickerOpen = true"
                >
                    {{ asset ? 'Change' : chooseLabel }}
                </Button>
                <Button
                    v-if="asset"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="remove"
                    >Remove</Button
                >
            </div>
            <p v-else class="text-xs text-muted-foreground">
                Your role cannot open the media library; ask an editor to set
                this.
            </p>
        </div>

        <MediaPicker
            v-if="canPick"
            v-model:open="pickerOpen"
            :kinds="kinds"
            :title="pickerTitle"
            :exclude-ids="[]"
            @insert="onInsert"
        />
    </div>
</template>
