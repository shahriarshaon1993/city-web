<script setup lang="ts">
import { ref } from 'vue';
import { MAX_WIDTH, MIN_WIDTH } from '@/lib/table-state';

// ADM-TBL-007: drag the header edge (live preview, commit on release), arrow keys step 10 px
// (Shift 50), double-click fits the content. The 12 px hit area sits over the 4 px visible line.
const props = defineProps<{ label: string; width: number }>();

const emit = defineEmits<{
    preview: [width: number];
    commit: [width: number];
    fit: [];
}>();

const dragging = ref(false);
let startX = 0;
let startWidth = 0;
let latest = 0;

function down(event: PointerEvent): void {
    event.preventDefault();
    event.stopPropagation();
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
    dragging.value = true;
    startX = event.clientX;
    startWidth = props.width;
    latest = props.width;
}

function move(event: PointerEvent): void {
    if (dragging.value) {
        latest = startWidth + event.clientX - startX;
        emit('preview', latest);
    }
}

function up(): void {
    if (dragging.value) {
        dragging.value = false;
        emit('commit', latest);
    }
}

function key(event: KeyboardEvent): void {
    const step = event.shiftKey ? 50 : 10;

    if (event.key === 'ArrowRight') {
        event.preventDefault();
        emit('commit', props.width + step);
    } else if (event.key === 'ArrowLeft') {
        event.preventDefault();
        emit('commit', props.width - step);
    }
}
</script>

<template>
    <span
        role="separator"
        aria-orientation="vertical"
        tabindex="0"
        :aria-label="`Resize ${label} column. Arrow keys change the width, double-click fits the content.`"
        :aria-valuenow="width"
        :aria-valuemin="MIN_WIDTH"
        :aria-valuemax="MAX_WIDTH"
        class="group/resize absolute inset-y-0 -right-1.5 z-[1] flex w-3 cursor-col-resize touch-none items-center justify-center outline-none"
        data-no-row-click
        @pointerdown="down"
        @pointermove="move"
        @pointerup="up"
        @pointercancel="up"
        @keydown="key"
        @dblclick.stop="emit('fit')"
        @click.stop
    >
        <span
            class="h-5 w-1 rounded-full bg-border transition-colors group-hover/resize:bg-primary group-focus-visible/resize:bg-primary"
            :class="dragging ? 'bg-primary' : ''"
        />
    </span>
</template>
