<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Phone } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import SocialIcon from '@/components/site/SocialIcon.vue';
import { Button } from '@/components/ui/button';
import { whatsappUrl } from '@/lib/contact';
import { cn } from '@/lib/utils';
import type { SiteShared } from '@/types/site';

// CMP-BAR-01: < 1024 px only, fixed bottom, 64 px + safe area. Shown once
// the page's `[data-hero]` element has left the viewport (at once on pages
// without a hero); hidden while a text field has focus (on-screen keyboard)
// and while the footer is visible. Hidden = inert, so it is never tabbable.
// PG-04 (4.3) makes Enquire open the enquiry sheet instead of linking.
const page = usePage<SiteShared>();
const site = computed(() => page.props.site);

const whatsapp = computed(() =>
    site.value.contact.whatsapp
        ? whatsappUrl(site.value.contact.whatsapp)
        : null,
);

/** Read by SiteLayout to lift bottom toasts above the bar. */
const shown = defineModel<boolean>('shown', { default: false });

const mounted = ref(false);
const pastHero = ref(false);
const footerVisible = ref(false);
const fieldFocused = ref(false);

const visible = computed(
    () =>
        mounted.value &&
        pastHero.value &&
        !footerVisible.value &&
        !fieldFocused.value,
);

watch(visible, (value) => {
    shown.value = value;
});

const NON_TEXT_INPUTS = new Set([
    'button',
    'checkbox',
    'color',
    'file',
    'hidden',
    'image',
    'radio',
    'range',
    'reset',
    'submit',
]);

function isTextField(target: EventTarget | null): boolean {
    if (!(target instanceof HTMLElement)) {
        return false;
    }

    if (target instanceof HTMLInputElement) {
        return !NON_TEXT_INPUTS.has(target.type);
    }

    return (
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement ||
        target.isContentEditable
    );
}

function onFocusIn(event: FocusEvent): void {
    fieldFocused.value = isTextField(event.target);
}

function onFocusOut(event: FocusEvent): void {
    fieldFocused.value = isTextField(event.relatedTarget);
}

let heroObserver: IntersectionObserver | undefined;
let footerObserver: IntersectionObserver | undefined;

function observeHero(): void {
    heroObserver?.disconnect();
    const hero = document.querySelector('[data-hero]');

    if (!hero) {
        pastHero.value = true;

        return;
    }

    pastHero.value = false;
    heroObserver = new IntersectionObserver(([entry]) => {
        pastHero.value = !entry.isIntersecting;
    });
    heroObserver.observe(hero);
}

// The layout persists: look for the new page's hero after each visit.
watch(() => page.url, observeHero, { flush: 'post' });

onMounted(() => {
    observeHero();

    const footer = document.getElementById('site-footer');

    if (footer) {
        footerObserver = new IntersectionObserver(([entry]) => {
            footerVisible.value = entry.isIntersecting;
        });
        footerObserver.observe(footer);
    }

    document.addEventListener('focusin', onFocusIn);
    document.addEventListener('focusout', onFocusOut);
    mounted.value = true;
});

onBeforeUnmount(() => {
    heroObserver?.disconnect();
    footerObserver?.disconnect();
    document.removeEventListener('focusin', onFocusIn);
    document.removeEventListener('focusout', onFocusOut);
});
</script>

<template>
    <nav
        aria-label="Quick contact"
        :inert="visible ? undefined : true"
        :class="
            cn(
                'fixed inset-x-0 bottom-0 z-(--z-mobile-bar) border-t border-border bg-background/95 px-4 pt-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] shadow-lg backdrop-blur-md transition-[translate,visibility] duration-(--dur-base) ease-(--ease-standard) sm:px-6 lg:hidden',
                !visible && 'invisible translate-y-full',
            )
        "
    >
        <ul class="mx-auto grid max-w-xl grid-cols-3 gap-2">
            <li v-if="site.contact.hotline">
                <Button
                    as="a"
                    :href="`tel:${site.contact.hotline}`"
                    variant="outline"
                    class="h-12 w-full gap-1.5 px-2"
                >
                    <Phone class="size-5" aria-hidden="true" />
                    Call
                </Button>
            </li>
            <li v-if="whatsapp">
                <Button
                    as="a"
                    :href="whatsapp"
                    target="_blank"
                    rel="noopener"
                    variant="outline"
                    class="h-12 w-full gap-1.5 px-2"
                >
                    <SocialIcon network="whatsapp" class="size-5" />
                    WhatsApp
                </Button>
            </li>
            <li>
                <Button
                    as-child
                    variant="accent"
                    class="h-12 w-full gap-1.5 px-2"
                >
                    <Link :href="site.header.cta.url">
                        {{ site.header.cta.label }}
                    </Link>
                </Button>
            </li>
        </ul>
    </nav>
</template>
