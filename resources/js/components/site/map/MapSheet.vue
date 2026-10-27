<script setup lang="ts">
import { X } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import { nextTick, onBeforeUnmount, ref, useTemplateRef, watch } from 'vue';
import MapProjectCard from '@/components/site/map/MapProjectCard.vue';
import ProjectMap from '@/components/site/map/ProjectMap.vue';
import { usePauseSmoothScroll } from '@/motion/bridge';
import { isMotionReduced } from '@/motion/preference';
import type { LatLng, MapProject } from '@/types/location';

// LAY-PROJ-LIST / LAY-LOC-DETAIL mobile map: a full-screen sheet (Reka
// Dialog: focus trap, Esc, scroll lock; Lenis paused) with the map and a
// bottom strip of mini cards synced to the markers — tapping a marker
// scrolls to its card, swiping the strip moves the active marker. The map
// mounts (and loads) only while the sheet is open.
const props = withDefaults(
    defineProps<{
        title: string;
        projects: MapProject[];
        label: string;
        centre?: LatLng | null;
        zoom?: number | null;
    }>(),
    { centre: null, zoom: null },
);

const open = defineModel<boolean>('open', { default: false });
usePauseSmoothScroll(() => open.value);

const active = ref<number | null>(null);
const strip = useTemplateRef<HTMLElement>('strip');

let observer: IntersectionObserver | null = null;
/** True while the strip scrolls because a marker was tapped. */
let scrollingTo = false;
let scrollTimer: ReturnType<typeof setTimeout> | undefined;

function observe(): void {
    observer?.disconnect();

    if (!strip.value) {
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (scrollingTo) {
                return;
            }

            const visible = entries.find((entry) => entry.isIntersecting);
            const id = Number(
                (visible?.target as HTMLElement | undefined)?.dataset.id,
            );

            if (visible && id) {
                active.value = id;
            }
        },
        { root: strip.value, threshold: 0.75 },
    );

    strip.value
        .querySelectorAll('[data-id]')
        .forEach((card) => observer?.observe(card));
}

watch(open, async (isOpen) => {
    if (isOpen) {
        active.value = props.projects[0]?.id ?? null;
        await nextTick();
        observe();
    } else {
        observer?.disconnect();
    }
});

watch(
    () => props.projects,
    async () => {
        active.value = props.projects[0]?.id ?? null;
        await nextTick();

        if (open.value) {
            observe();
        }
    },
);

function onMarker(id: number | null): void {
    active.value = id;

    const card = strip.value?.querySelector<HTMLElement>(`[data-id="${id}"]`);

    if (!card) {
        return;
    }

    scrollingTo = true;
    clearTimeout(scrollTimer);
    card.scrollIntoView({
        behavior: isMotionReduced() ? 'auto' : 'smooth',
        block: 'nearest',
        inline: 'center',
    });
    scrollTimer = setTimeout(() => {
        scrollingTo = false;
    }, 700);
}

onBeforeUnmount(() => {
    observer?.disconnect();
    clearTimeout(scrollTimer);
});
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogContent
                :aria-describedby="undefined"
                class="fixed inset-0 z-(--z-drawer) flex flex-col bg-background text-foreground duration-320 outline-none data-[state=closed]:animate-out data-[state=closed]:slide-out-to-bottom data-[state=open]:animate-in data-[state=open]:slide-in-from-bottom"
            >
                <div
                    class="flex min-h-16 shrink-0 items-center justify-between gap-4 border-b border-border px-4 sm:px-6"
                >
                    <DialogTitle class="font-display text-h4">
                        {{ title }}
                    </DialogTitle>
                    <DialogClose
                        class="-mr-2 flex size-11 items-center justify-center rounded-md hover:bg-muted"
                        aria-label="Close map"
                    >
                        <X class="size-5" aria-hidden="true" />
                    </DialogClose>
                </div>

                <ProjectMap
                    v-if="open"
                    :active="active"
                    :projects="projects"
                    :label="label"
                    :centre="centre"
                    :zoom="zoom"
                    :popups="false"
                    eager
                    class="min-h-0 flex-1"
                    @update:active="onMarker"
                />

                <ul
                    v-if="projects.length > 0"
                    ref="strip"
                    data-lenis-prevent
                    class="flex shrink-0 snap-x snap-mandatory [scrollbar-width:none] gap-3 overflow-x-auto border-t border-border px-4 py-4 pb-[max(1rem,env(safe-area-inset-bottom))] sm:px-6 [&::-webkit-scrollbar]:hidden"
                    aria-label="Projects on the map"
                >
                    <li
                        v-for="project in projects"
                        :key="project.id"
                        :data-id="project.id"
                        class="w-[85%] max-w-sm shrink-0 snap-center"
                    >
                        <MapProjectCard
                            :project="project"
                            :active="project.id === active"
                        />
                    </li>
                </ul>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
