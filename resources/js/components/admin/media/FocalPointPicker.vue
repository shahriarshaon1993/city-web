<script setup lang="ts">
import { computed, ref, useId, watch } from 'vue';

// CMP-ADM-FOCAL: click or drag the crosshair; arrow keys move it 1 % (Shift 10 %); the previews
// show how 16:9, 4:3, 1:1 and 4:5 crops keep the point in view (object-position = the focal point,
// as the public site crops it). The value is percent of width / height (CMS-MED-004).
const props = defineProps<{
    src: string;
    /** Intrinsic size, so the stage keeps the image's ratio before it loads. */
    width?: number | null;
    height?: number | null;
    alt?: string;
}>();

const model = defineModel<{ x: number; y: number }>({ required: true });

// The latest point, so quick key repeats build on each other before the parent re-renders.
const point = ref({ ...model.value });

watch(model, (value) => {
    point.value = { ...value };
});

function set(value: { x: number; y: number }): void {
    point.value = value;
    model.value = value;
}

const id = useId();
const stage = ref<HTMLElement | null>(null);
const dragging = ref(false);

const ratio = computed(() =>
    props.width && props.height ? `${props.width} / ${props.height}` : '4 / 3',
);
const position = computed(() => `${model.value.x}% ${model.value.y}%`);

const PREVIEWS = [
    { label: '16:9', ratio: '16 / 9' },
    { label: '4:3', ratio: '4 / 3' },
    { label: '1:1', ratio: '1 / 1' },
    { label: '4:5', ratio: '4 / 5' },
] as const;

const clamp = (value: number): number =>
    Math.min(100, Math.max(0, Math.round(value)));

function setFromPointer(event: PointerEvent): void {
    const box = stage.value?.getBoundingClientRect();

    if (!box || box.width === 0 || box.height === 0) {
        return;
    }

    set({
        x: clamp(((event.clientX - box.left) / box.width) * 100),
        y: clamp(((event.clientY - box.top) / box.height) * 100),
    });
}

function onPointerDown(event: PointerEvent): void {
    dragging.value = true;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
    setFromPointer(event);
}

function onPointerMove(event: PointerEvent): void {
    if (dragging.value) {
        setFromPointer(event);
    }
}

function onPointerUp(event: PointerEvent): void {
    dragging.value = false;
    (event.currentTarget as HTMLElement).releasePointerCapture(event.pointerId);
}

function onKeydown(event: KeyboardEvent): void {
    const step = event.shiftKey ? 10 : 1;
    const moves: Record<string, [number, number]> = {
        ArrowLeft: [-step, 0],
        ArrowRight: [step, 0],
        ArrowUp: [0, -step],
        ArrowDown: [0, step],
    };
    const move = moves[event.key];

    if (event.key === 'Home') {
        event.preventDefault();
        set({ x: 50, y: 50 });

        return;
    }

    if (move) {
        event.preventDefault();
        set({
            x: clamp(point.value.x + move[0]),
            y: clamp(point.value.y + move[1]),
        });
    }
}
</script>

<template>
    <div class="grid gap-4">
        <div
            ref="stage"
            role="slider"
            tabindex="0"
            :aria-labelledby="`${id}-label`"
            :aria-describedby="`${id}-help`"
            aria-valuemin="0"
            aria-valuemax="100"
            :aria-valuenow="model.x"
            :aria-valuetext="`${model.x}% from the left, ${model.y}% from the top`"
            class="relative w-full cursor-crosshair touch-none overflow-hidden rounded-md bg-muted outline-none select-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
            :style="{ aspectRatio: ratio }"
            @pointerdown="onPointerDown"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointercancel="onPointerUp"
            @keydown="onKeydown"
        >
            <img
                :src="src"
                :alt="alt ?? ''"
                class="pointer-events-none absolute inset-0 size-full object-contain"
                draggable="false"
            />
            <span
                class="pointer-events-none absolute size-8 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow-[0_0_0_1px_rgb(0_0_0/0.45)]"
                :style="{ left: `${model.x}%`, top: `${model.y}%` }"
                aria-hidden="true"
            >
                <span
                    class="absolute top-1/2 left-1/2 size-1.5 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white shadow-[0_0_0_1px_rgb(0_0_0/0.45)]"
                />
            </span>
        </div>

        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <p :id="`${id}-label`" class="text-sm font-medium">Focal point</p>
            <p class="text-xs text-muted-foreground tabular-nums">
                {{ model.x }}% · {{ model.y }}%
            </p>
        </div>
        <p :id="`${id}-help`" class="-mt-3 text-xs text-muted-foreground">
            Click or drag to the part that must stay in view when the image is
            cropped. Arrow keys move it 1% (Shift 10%); Home centres it.
        </p>

        <ul class="grid grid-cols-4 items-end gap-2" aria-label="Crop previews">
            <li
                v-for="preview in PREVIEWS"
                :key="preview.label"
                class="grid gap-1"
            >
                <img
                    :src="src"
                    alt=""
                    class="w-full rounded-sm bg-muted object-cover"
                    :style="{
                        aspectRatio: preview.ratio,
                        objectPosition: position,
                    }"
                    draggable="false"
                />
                <span class="text-center text-xs text-muted-foreground">{{
                    preview.label
                }}</span>
            </li>
        </ul>
    </div>
</template>
