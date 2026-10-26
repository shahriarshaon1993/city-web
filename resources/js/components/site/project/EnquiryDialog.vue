<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowRight, Phone, X } from '@lucide/vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import { computed } from 'vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { buttonVariants } from '@/components/ui/button';
import { whatsappUrl } from '@/lib/contact';
import { formatPhone } from '@/lib/format';
import { usePauseSmoothScroll } from '@/motion/bridge';
import type { EnquiryKind, ProjectCopy, ProjectDetail } from '@/types/project';
import type { SitePageProps } from '@/types/site';

// CMP-ENQ-01 dialog shells for Enquire / Book a site visit / Brochure: a
// bottom sheet below 640 px, a centred dialog above (CMP-MODAL-01, MOT-MODAL).
// Reka traps focus, closes on Esc and locks scrolling; Lenis pauses. The
// dialogs open from several buttons (panel, documents, mobile bar), so focus
// returns to `returnFocus` rather than to a Reka trigger. The lead forms
// (CMP-FORM-02, project prefilled) arrive in 7.1 in the form slot; until
// then the shell offers the hotline, WhatsApp and the contact page.
const props = defineProps<{
    kind: EnquiryKind;
    project: ProjectDetail;
    copy: ProjectCopy;
    /** No-JS / fallback contact page URL for this kind. */
    contactUrl: string;
    returnFocus: HTMLElement | null;
}>();

const open = defineModel<boolean>('open', { default: false });
usePauseSmoothScroll(() => open.value);

const page = usePage<SitePageProps>();
const contact = computed(() => page.props.site.contact);

const texts = computed(() => {
    const { copy } = props;
    const pick = {
        enquire: [copy.enquire_title, copy.enquire_text],
        visit: [copy.visit_title, copy.visit_text],
        brochure: [copy.brochure_title, copy.brochure_text],
    }[props.kind];

    return {
        title: pick[0].replace('{project}', props.project.name),
        text: pick[1],
    };
});

const whatsapp = computed(() => {
    const number = props.project.enquiry.whatsapp ?? contact.value.whatsapp;

    return number
        ? whatsappUrl(number, props.project.enquiry.whatsapp_text)
        : null;
});

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
                class="fixed z-(--z-drawer) flex flex-col bg-background text-foreground shadow-xl outline-none max-sm:inset-x-0 max-sm:bottom-0 max-sm:max-h-[90svh] max-sm:rounded-t-xl max-sm:duration-320 max-sm:data-[state=closed]:animate-out max-sm:data-[state=closed]:slide-out-to-bottom max-sm:data-[state=open]:animate-in max-sm:data-[state=open]:slide-in-from-bottom sm:top-1/2 sm:left-1/2 sm:max-h-[min(90svh,44rem)] sm:w-[calc(100%-3rem)] sm:max-w-lg sm:-translate-x-1/2 sm:-translate-y-1/2 sm:rounded-xl sm:duration-200 sm:data-[state=closed]:animate-out sm:data-[state=closed]:fade-out-0 sm:data-[state=closed]:zoom-out-95 sm:data-[state=open]:animate-in sm:data-[state=open]:fade-in-0 sm:data-[state=open]:zoom-in-95"
                @close-auto-focus="restoreFocus"
            >
                <div
                    class="flex shrink-0 items-start justify-between gap-4 border-b border-border px-5 pt-5 pb-4 sm:px-8 sm:pt-7"
                >
                    <div class="flex min-w-0 flex-col gap-2">
                        <p
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ project.location.area }},
                            {{ project.location.city }}
                        </p>
                        <DialogTitle class="font-display text-h3 text-balance">
                            {{ texts.title }}
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
                        class="text-body text-pretty text-muted-foreground"
                    >
                        {{ texts.text }}
                    </DialogDescription>

                    <!-- 7.1: CMP-FORM-02 for this kind, project prefilled. -->
                    <slot />

                    <div class="flex flex-col gap-3">
                        <p
                            class="text-overline text-muted-foreground uppercase"
                        >
                            {{ copy.dialog_alternative }}
                        </p>
                        <ul
                            class="divide-y divide-border border-y border-border"
                        >
                            <li v-if="contact.hotline">
                                <a
                                    :href="`tel:${contact.hotline}`"
                                    class="flex min-h-13 items-center gap-3 text-sm transition-colors duration-(--dur-fast) hover:text-primary"
                                >
                                    <Phone
                                        class="size-5 text-primary"
                                        stroke-width="1.5"
                                        aria-hidden="true"
                                    />
                                    <span class="flex-1">Call the hotline</span>
                                    <span class="tabular text-muted-foreground">
                                        {{ formatPhone(contact.hotline) }}
                                    </span>
                                </a>
                            </li>
                            <li v-if="whatsapp">
                                <a
                                    :href="whatsapp"
                                    target="_blank"
                                    rel="noopener"
                                    class="flex min-h-13 items-center gap-3 text-sm transition-colors duration-(--dur-fast) hover:text-primary"
                                >
                                    <SocialIcon
                                        network="whatsapp"
                                        class="size-5 text-whatsapp"
                                    />
                                    <span class="flex-1">
                                        Chat on WhatsApp
                                        <span class="sr-only"
                                            >(opens in a new tab)</span
                                        >
                                    </span>
                                </a>
                            </li>
                            <li>
                                <a
                                    :href="contactUrl"
                                    class="group/contact flex min-h-13 items-center gap-3 text-sm transition-colors duration-(--dur-fast) hover:text-primary"
                                >
                                    <span class="flex-1"
                                        >Write to us on the contact page</span
                                    >
                                    <ArrowRight
                                        class="size-4 transition-transform duration-(--dur-base) group-hover/contact:translate-x-1"
                                        aria-hidden="true"
                                    />
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </DialogContent>
        </DialogPortal>
    </DialogRoot>
</template>
