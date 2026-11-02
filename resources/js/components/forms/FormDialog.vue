<script setup lang="ts">
import { X } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import { buttonVariants } from '@/components/ui/button';
import { usePauseSmoothScroll } from '@/motion/bridge';

// CMP-MODAL-01 dialog for lead forms: a bottom sheet below 640 px, a centred
// dialog above (MOT-MODAL). Reka traps focus, closes on Esc and locks
// scrolling; Lenis pauses. The form scrolls inside the body. Dialogs open
// from several buttons, so focus returns to `returnFocus` rather than to a
// Reka trigger.
const props = defineProps<{
    title: string;
    description?: string;
    /** Small uppercase line above the title (e.g. the project's location). */
    overline?: string;
    returnFocus?: HTMLElement | null;
}>();

const open = defineModel<boolean>('open', { default: false });
usePauseSmoothScroll(() => open.value);

function restoreFocus(event: Event): void {
    if (props.returnFocus?.isConnected) {
        event.preventDefault();
        props.returnFocus.focus();
    }
}
</script>

<template>
    <DialogRoot v-model:open="open">
        <DialogPortal>
            <DialogOverlay
                class="fixed inset-0 z-(--z-overlay) bg-stone-950/60 backdrop-blur-sm duration-200 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0"
            />
            <DialogContent
                class="fixed z-(--z-drawer) flex flex-col bg-background text-foreground shadow-xl outline-none max-sm:inset-x-0 max-sm:bottom-0 max-sm:max-h-[90svh] max-sm:rounded-t-xl max-sm:duration-320 max-sm:data-[state=closed]:animate-out max-sm:data-[state=closed]:slide-out-to-bottom max-sm:data-[state=open]:animate-in max-sm:data-[state=open]:slide-in-from-bottom sm:top-1/2 sm:left-1/2 sm:max-h-[min(90svh,48rem)] sm:w-[calc(100%-3rem)] sm:max-w-lg sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-xl sm:duration-200 sm:data-[state=closed]:animate-out sm:data-[state=closed]:fade-out-0 sm:data-[state=closed]:zoom-out-95 sm:data-[state=open]:animate-in sm:data-[state=open]:fade-in-0 sm:data-[state=open]:zoom-in-95"
                @close-auto-focus="restoreFocus"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-border px-5 pt-5 pb-4 sm:px-8 sm:pt-7"
                >
                    <div class="flex min-w-0 flex-col gap-2">
                        <p
                            v-if="overline"
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ overline }}
                        </p>
                        <DialogTitle class="font-display text-h3 text-balance">
                            {{ title }}
                        </DialogTitle>
                    </div>
                    <DialogClose
                        aria-label="Close"
                        :class="
                            buttonVariants({ variant: 'ghost', size: 'icon' })
                        "
                        class="-mt-1 -mr-2 shrink-0"
                    >
                        <X class="size-5" aria-hidden="true" />
                    </DialogClose>
                </div>

                <div
                    data-lenis-prevent
                    class="flex min-h-0 flex-1 flex-col gap-6 overflow-y-auto overscroll-contain px-5 py-6 pb-[max(1.5rem,env(safe-area-inset-bottom))] sm:px-8 sm:pb-8"
                >
                    <DialogDescription
                        v-if="description"
                        class="text-body text-pretty text-muted-foreground"
                    >
                        {{ description }}
                    </DialogDescription>
                    <slot />
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
