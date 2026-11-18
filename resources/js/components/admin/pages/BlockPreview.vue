<script setup lang="ts">
import { useDebounceFn, useElementSize, useEventListener } from '@vueuse/core';
import { Monitor, RefreshCw, Smartphone, Tablet } from '@lucide/vue';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { sendJson } from '@/lib/http';
import { preview as previewRoute } from '@/routes/admin/pages';
import type { EditorBlock, PreviewResult } from '@/types';
import { blocksPayload } from './context';

// CMP-ADM-BUILDER live preview (CMS-PB-002): the public layout in an iframe at 1280 / 834 / 390 px,
// scaled to fit. The blocks of the unsaved editor state are sent to the server 600 ms after the last edit
// (it resolves them exactly like the public site, and validates each one), and pushed into the frame by
// postMessage, so an edit shows without a reload. Hovering a block in the frame outlines it in the list
// and clicking it selects it, and the other way round (design/admin.md §12.5).
const props = defineProps<{
    frameUrl: string;
    pageId: number | null;
    title: string;
    blocks: EditorBlock[];
    selectedId: number | null;
    hoverId: number | null;
}>();

const emit = defineEmits<{
    select: [id: number];
    hover: [id: number | null];
    result: [result: PreviewResult];
}>();

const DEVICES = [
    { value: '1280', label: 'Desktop', width: 1280, icon: Monitor },
    { value: '834', label: 'Tablet', width: 834, icon: Tablet },
    { value: '390', label: 'Phone', width: 390, icon: Smartphone },
] as const;

const device = ref<string>('1280');
const width = computed(() => Number(device.value));
const stage = useTemplateRef<HTMLElement>('stage');
const frame = useTemplateRef<HTMLIFrameElement>('frame');
const { width: stageWidth } = useElementSize(stage);

/** Zoom to fit: the frame keeps its device width and is scaled down to the space there is. */
const scale = computed(() =>
    stageWidth.value > 0 ? Math.min(1, stageWidth.value / width.value) : 1,
);
const HEIGHT = 760;

const ready = ref(false);
const state = ref<'idle' | 'updating' | 'failed'>('idle');
let sequence = 0;

function post(message: Record<string, unknown>): void {
    frame.value?.contentWindow?.postMessage(
        { source: 'gc-builder', ...message },
        window.location.origin,
    );
}

async function refresh(): Promise<void> {
    const ticket = ++sequence;
    state.value = 'updating';

    try {
        const result = await sendJson<PreviewResult>(
            'POST',
            previewRoute.url(),
            {
                page_id: props.pageId,
                title: props.title,
                blocks: blocksPayload(props.blocks),
            },
        );

        // An older answer must not overwrite a newer one.
        if (ticket !== sequence) {
            return;
        }

        state.value = 'idle';
        emit('result', result);

        if (ready.value) {
            post({
                type: 'blocks',
                blocks: result.blocks,
                starts_with_hero: result.starts_with_hero,
            });
            post({
                type: 'highlight',
                selected: props.selectedId,
                hover: props.hoverId,
            });
        }
    } catch {
        if (ticket === sequence) {
            state.value = 'failed';
        }
    }
}

const debounced = useDebounceFn(refresh, 600, { maxWait: 2500 });

// What matters for the preview: the blocks and the title. A JSON signature keeps deep edits (a rich
// text character, a list item) from being missed and avoids requests when nothing changed.
const signature = computed(() =>
    JSON.stringify([props.title, blocksPayload(props.blocks)]),
);
let last = '';

watch(signature, (value) => {
    if (value !== last) {
        last = value;
        void debounced();
    }
});

watch(
    () => [props.selectedId, props.hoverId] as const,
    ([selected, hover]) => {
        if (ready.value) {
            post({ type: 'highlight', selected, hover });
        }
    },
);

// A block picked in the list scrolls into view in the frame.
watch(
    () => props.selectedId,
    (id) => {
        if (ready.value && id !== null) {
            post({ type: 'scroll', id });
        }
    },
);

useEventListener(window, 'message', (event: MessageEvent) => {
    const data = event.data as {
        source?: string;
        type?: string;
        id?: number | null;
    } | null;

    if (
        event.origin !== window.location.origin ||
        event.source !== frame.value?.contentWindow ||
        data?.source !== 'gc-builder-frame'
    ) {
        return;
    }

    if (data.type === 'ready') {
        ready.value = true;
        last = signature.value;
        void refresh();
    } else if (data.type === 'hover') {
        emit('hover', data.id ?? null);
    } else if (data.type === 'select' && typeof data.id === 'number') {
        emit('select', data.id);
    }
});

onMounted(() => {
    last = signature.value;
});
</script>

<template>
    <section
        class="flex min-w-0 flex-col rounded-xl border bg-card"
        aria-labelledby="preview-title"
    >
        <header
            class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3"
        >
            <div class="min-w-0">
                <h2 id="preview-title" class="text-sm font-medium">
                    Live preview
                </h2>
                <p
                    class="text-xs text-muted-foreground"
                    role="status"
                    aria-live="polite"
                >
                    <template v-if="state === 'updating'">Updating…</template>
                    <template v-else-if="state === 'failed'"
                        >The preview could not update.</template
                    >
                    <template v-else-if="!ready">Loading the preview…</template>
                    <template v-else
                        >Shows your unsaved changes. Click a block to edit
                        it.</template
                    >
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button
                    v-if="state === 'failed'"
                    type="button"
                    variant="outline"
                    size="sm"
                    @click="refresh"
                >
                    <RefreshCw aria-hidden="true" />
                    Try again
                </Button>
                <ToggleGroup
                    v-model="device"
                    type="single"
                    variant="outline"
                    size="sm"
                    aria-label="Preview width"
                >
                    <ToggleGroupItem
                        v-for="item in DEVICES"
                        :key="item.value"
                        :value="item.value"
                        :aria-label="`${item.label}, ${item.width} pixels wide`"
                        :title="`${item.label} · ${item.width} px`"
                    >
                        <component
                            :is="item.icon"
                            class="size-4"
                            aria-hidden="true"
                        />
                    </ToggleGroupItem>
                </ToggleGroup>
            </div>
        </header>

        <div ref="stage" class="overflow-hidden bg-muted/40 p-0">
            <div
                class="mx-auto overflow-hidden bg-background shadow-sm"
                :style="{
                    width: `${width * scale}px`,
                    height: `${HEIGHT * scale}px`,
                }"
            >
                <iframe
                    ref="frame"
                    :src="frameUrl"
                    title="Preview of the page as visitors see it"
                    class="block origin-top-left border-0 bg-background"
                    :style="{
                        width: `${width}px`,
                        height: `${HEIGHT}px`,
                        transform: `scale(${scale})`,
                    }"
                />
            </div>
        </div>
    </section>
</template>
