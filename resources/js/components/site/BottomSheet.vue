<script setup lang="ts">
import { X } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
    DialogTrigger,
} from 'reka-ui';
import { computed, ref } from 'vue';
import { buttonVariants } from '@/components/ui/button';
import { usePauseSmoothScroll } from '@/motion/bridge';

// CMP-MODAL-01 bottom sheet (mobile search, later the 4.1 filters): max
// 90svh, drag handle, swipe down to close; Reka Dialog traps focus, closes
// on Esc, returns focus to the trigger and locks body scroll. MOT-MODAL:
// 320 ms translate (instant under reduced motion). Swipe is hand-written
// instead of vaul-vue; only `transform` moves while dragging (MOT-R01).
defineProps<{
    title: string;
}>();

const open = defineModel<boolean>('open', { default: false });

// §8.3: Lenis stops while the sheet is open; its body scrolls natively.
usePauseSmoothScroll(() => open.value);

/** Drag distance after which releasing closes the sheet. */
const CLOSE_DISTANCE = 96;

const dragY = ref(0);
let startY: number | null = null;

function onPointerDown(event: PointerEvent): void {
    startY = event.clientY;
    (event.currentTarget as HTMLElement).setPointerCapture(event.pointerId);
}

function onPointerMove(event: PointerEvent): void {
    if (startY !== null) {
        dragY.value = Math.max(0, event.clientY - startY);
    }
}

function onPointerUp(): void {
    if (dragY.value > CLOSE_DISTANCE) {
        open.value = false;
    }

    startY = null;
    dragY.value = 0;
}

const dragStyle = computed(() =>
    dragY.value > 0
        ? { transform: `translateY(${dragY.value}px)`, transition: 'none' }
        : undefined,
);
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogTrigger as-child>
            <slot name="trigger" />
        </DialogTrigger>
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-(--z-overlay) bg-stone-950/60 backdrop-blur-sm duration-200 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                :aria-describedby="undefined"
                class="fixed inset-x-0 bottom-0 z-(--z-drawer) flex max-h-[90svh] flex-col rounded-t-xl bg-background text-foreground shadow-xl duration-320 outline-none data-[state=closed]:animate-out data-[state=closed]:slide-out-to-bottom data-[state=open]:animate-in data-[state=open]:slide-in-from-bottom"
                :style="dragStyle"
            >
                <div
                    class="shrink-0 cursor-grab touch-none px-4 pt-2 active:cursor-grabbing sm:px-6"
                    @pointerdown="onPointerDown"
                    @pointermove="onPointerMove"
                    @pointerup="onPointerUp"
                    @pointercancel="onPointerUp"
                >
                    <span
                        class="mx-auto block h-1.5 w-12 rounded-full bg-border"
                        aria-hidden="true"
                    />
                    <div class="flex items-center justify-between gap-4 py-2">
                        <DialogTitle class="font-display text-h4">
                            {{ title }}
                        </DialogTitle>
                        <DialogClose
                            aria-label="Close"
                            :class="
                                buttonVariants({
                                    variant: 'ghost',
                                    size: 'icon',
                                })
                            "
                            @pointerdown.stop
                        >
                            <X class="size-5" aria-hidden="true" />
                        </DialogClose>
                    </div>
                </div>
                <div
                    data-lenis-prevent
                    class="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 pb-4 sm:px-6"
                >
                    <slot />
                </div>
                <div
                    v-if="$slots.footer"
                    class="shrink-0 border-t border-border px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] sm:px-6"
                >
                    <slot name="footer" />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
