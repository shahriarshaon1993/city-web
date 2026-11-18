<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import BlockRenderer from '@/components/blocks/BlockRenderer.vue';
import type { PageBlock } from '@/types/blocks';

// CMS-PB-002: the page inside the admin's preview iframe. It draws whatever block list the admin pushes
// in (postMessage, same origin only) with the public BlockRenderer, so the preview is the real site.
// Hovering a block outlines it, clicking selects it (reported back to the admin); links and forms are
// inert. Motion stays off here: a preview that re-renders on every keystroke must not replay reveals.
const props = defineProps<{
    blocks: PageBlock[];
}>();

const blocks = ref<PageBlock[]>(props.blocks);
const page = usePage();
const parentWindow = (): Window | null =>
    window.parent === window ? null : window.parent;

function tell(message: Record<string, unknown>): void {
    parentWindow()?.postMessage(
        { source: 'gc-builder-frame', ...message },
        window.location.origin,
    );
}

const idOf = (target: EventTarget | null): number | null => {
    const element = (target as Element | null)?.closest?.('[data-block-id]');

    return element ? Number(element.getAttribute('data-block-id')) : null;
};

function highlight(selected: number | null, hover: number | null): void {
    document.querySelectorAll('[data-block-id]').forEach((element) => {
        const id = Number(element.getAttribute('data-block-id'));
        const state =
            id === selected ? 'selected' : id === hover ? 'hover' : '';

        if (state) {
            element.setAttribute('data-builder-state', state);
        } else {
            element.removeAttribute('data-builder-state');
        }
    });
}

let selected: number | null = null;
let hover: number | null = null;

function onMessage(event: MessageEvent): void {
    const data = event.data as Record<string, any> | null;

    if (
        event.origin !== window.location.origin ||
        event.source !== parentWindow() ||
        data?.source !== 'gc-builder'
    ) {
        return;
    }

    if (data.type === 'blocks') {
        blocks.value = data.blocks as PageBlock[];
        // The header sits over the first block only when that is a hero or a photo page header.
        (page.props as Record<string, unknown>).starts_with_hero =
            data.starts_with_hero === true;
        requestAnimationFrame(() => highlight(selected, hover));
    } else if (data.type === 'highlight') {
        selected = data.selected ?? null;
        hover = data.hover ?? null;
        highlight(selected, hover);
    } else if (data.type === 'scroll') {
        document
            .querySelector(`[data-block-id="${Number(data.id)}"]`)
            ?.scrollIntoView({ block: 'start', behavior: 'smooth' });
    }
}

let lastHover: number | null = null;

function onMove(event: MouseEvent): void {
    const id = idOf(event.target);

    if (id !== lastHover) {
        lastHover = id;
        tell({ type: 'hover', id });
    }
}

function onLeave(): void {
    lastHover = null;
    tell({ type: 'hover', id: null });
}

function onClick(event: MouseEvent): void {
    // Nothing in the preview navigates, submits or opens a lightbox: a click only selects the block.
    event.preventDefault();
    event.stopPropagation();

    const id = idOf(event.target);

    if (id !== null) {
        tell({ type: 'select', id });
    }
}

function onSubmit(event: Event): void {
    event.preventDefault();
    event.stopPropagation();
}

onMounted(() => {
    document.documentElement.setAttribute('data-reduce-motion', '');
    document.documentElement.setAttribute('data-builder-frame', '');
    window.addEventListener('message', onMessage);
    document.addEventListener('mousemove', onMove);
    document.documentElement.addEventListener('mouseleave', onLeave);
    document.addEventListener('click', onClick, true);
    document.addEventListener('submit', onSubmit, true);
    tell({ type: 'ready' });
});

onBeforeUnmount(() => {
    window.removeEventListener('message', onMessage);
    document.removeEventListener('mousemove', onMove);
    document.documentElement.removeEventListener('mouseleave', onLeave);
    document.removeEventListener('click', onClick, true);
    document.removeEventListener('submit', onSubmit, true);
});
</script>

<template>
    <BlockRenderer :blocks="blocks" editable />
</template>

<style>
/* The cookie banner is not part of the page being edited. */
[data-builder-frame] section[role='region'].fixed {
    display: none;
}

[data-block-id] {
    cursor: pointer;
}

[data-builder-state] {
    outline: 2px solid var(--color-teal-600, #027685);
    outline-offset: -2px;
}

[data-builder-state='selected'] {
    outline-width: 3px;
}
</style>
