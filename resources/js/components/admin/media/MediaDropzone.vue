<script setup lang="ts">
import { ChevronDown, Upload } from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import { useMediaUploads } from '@/composables/useMediaUploads';
import type { UploadBatchResult } from '@/composables/useMediaUploads';
import { ACCEPT, limitsSummary } from '@/lib/media-upload';
import { cn } from '@/lib/utils';
import type { MediaKind, MediaLimits } from '@/types';

// CMP-ADM-MEDIA dropzone ("Choose files or drop them here"): states the allowed types, sizes and
// pixel limit up front and refuses wrong files before uploading (VAL-03, CMS-MED-003).
const props = withDefaults(
    defineProps<{
        limits: MediaLimits;
        /** New files land here; null = Unfiled. */
        folderId?: number | null;
        /** Kinds the field accepts (picker preset); all when absent. */
        kinds?: MediaKind[];
        multiple?: boolean;
        compact?: boolean;
    }>(),
    { folderId: null, kinds: undefined, multiple: true, compact: false },
);

const emit = defineEmits<{ done: [result: UploadBatchResult] }>();

const id = useId();
const input = ref<HTMLInputElement | null>(null);
const over = ref(false);
const { add } = useMediaUploads();

const summary = computed(() => limitsSummary(props.limits, props.kinds));
const megapixels = computed(() => props.limits.max_pixels / 1_000_000);

async function take(
    files: FileList | File[] | null | undefined,
): Promise<void> {
    const list = Array.from(files ?? []);

    if (list.length === 0) {
        return;
    }

    const result = await add(props.multiple ? list : list.slice(0, 1), {
        limits: props.limits,
        folderId: props.folderId,
        kinds: props.kinds,
    });

    emit('done', result);
}

function onDrop(event: DragEvent): void {
    over.value = false;
    void take(event.dataTransfer?.files);
}

function onChange(event: Event): void {
    const target = event.target as HTMLInputElement;

    void take(target.files);
    target.value = '';
}

defineExpose({ open: () => input.value?.click() });
</script>

<template>
    <div
        :class="
            cn(
                'relative grid place-items-center gap-2 rounded-xl border border-dashed text-center transition-colors duration-(--dur-fast)',
                compact ? 'p-4' : 'p-6',
                over
                    ? 'border-primary bg-primary/10'
                    : 'border-primary/40 bg-primary/[0.04] hover:border-primary/70 hover:bg-primary/[0.07]',
            )
        "
        @dragenter.prevent="over = true"
        @dragover.prevent="over = true"
        @dragleave.prevent="over = false"
        @drop.prevent="onDrop"
    >
        <span
            :class="
                cn(
                    'grid place-items-center rounded-full bg-primary/10 text-primary transition-transform duration-(--dur-fast)',
                    compact ? 'size-10' : 'size-12',
                    over && 'scale-110',
                )
            "
        >
            <Upload class="size-5" aria-hidden="true" />
        </span>
        <p class="text-sm leading-snug">
            <button
                type="button"
                class="font-medium text-primary underline-offset-4 hover:underline focus-visible:underline focus-visible:outline-none"
                :aria-describedby="`${id}-limits`"
                @click="input?.click()"
            >
                Choose {{ multiple ? 'files' : 'a file' }}
            </button>
            <span class="block text-muted-foreground"
                >or drop {{ multiple ? 'them' : 'it' }} here</span
            >
        </p>
        <details v-if="compact" class="group/limits w-full text-xs">
            <summary
                class="mx-auto flex w-fit cursor-pointer list-none items-center gap-1 rounded-sm text-muted-foreground outline-none marker:hidden hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring [&::-webkit-details-marker]:hidden"
            >
                Upload limits
                <ChevronDown
                    class="size-3 transition-transform group-open/limits:rotate-180 motion-reduce:transition-none"
                    aria-hidden="true"
                />
            </summary>
            <p
                :id="`${id}-limits`"
                class="mt-1.5 leading-relaxed text-muted-foreground"
            >
                {{ summary }}. Images up to {{ megapixels }} megapixels.
            </p>
        </details>
        <p v-else :id="`${id}-limits`" class="text-xs text-muted-foreground">
            {{ summary }}. Images up to {{ megapixels }} megapixels.
        </p>
        <input
            ref="input"
            type="file"
            class="sr-only"
            tabindex="-1"
            aria-hidden="true"
            :accept="ACCEPT"
            :multiple="multiple"
            @change="onChange"
        />
    </div>
</template>
