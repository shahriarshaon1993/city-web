<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import {
    computed,
    defineAsyncComponent,
    onMounted,
    ref,
    useTemplateRef,
    watch,
} from 'vue';
import CompareBar from '@/components/site/compare/CompareBar.vue';
import ConsentBanner from '@/components/site/consent/ConsentBanner.vue';
import MobileContactBar from '@/components/site/MobileContactBar.vue';
import SeoHead from '@/components/site/SeoHead.vue';
import SkipLink from '@/components/site/SkipLink.vue';
import SiteFooter from '@/components/site/footer/SiteFooter.vue';
import SiteHeader from '@/components/site/header/SiteHeader.vue';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useAnalytics } from '@/composables/useAnalytics';
import { useCallbackDialog } from '@/composables/useCallbackDialog';
import { useConsent } from '@/composables/useConsent';
import { useNavigationFeedback } from '@/composables/useNavigationFeedback';
import { captureAttribution } from '@/lib/forms/attribution';
import { startMotion, stopMotion } from '@/motion/bridge';
import { useReducedMotion } from '@/motion/preference';
import type { SitePageProps } from '@/types/site';

// Persistent public layout (ARC-010), default for every pages/Site/* page
// (app.ts): header state survives navigation. Pages render inside <main>
// and must not add their own; breadcrumbs are rendered by the pages.
const page = usePage<SitePageProps>();

// SEO-ANA-001…003: consent defaults, virtual page views, contact clicks.
useAnalytics();
const consent = useConsent();

// SEO-PERF-004: client-only, on-demand UI stays out of the initial route. The
// two dialogs (forms, switches, selects) load when first opened, the toaster
// after hydration; none of them renders anything before then.
const CallbackDialog = defineAsyncComponent(
    () => import('@/components/site/forms/CallbackDialog.vue'),
);
const ConsentPreferences = defineAsyncComponent(
    () => import('@/components/site/consent/ConsentPreferences.vue'),
);
const Toaster = defineAsyncComponent(() =>
    import('@/components/ui/sonner').then((module) => module.Toaster),
);
const callbackDialog = useCallbackDialog();
const callbackLoaded = ref(false);
const preferencesLoaded = ref(false);
const toasterLoaded = ref(false);

watch(callbackDialog.isOpen, (open) => {
    callbackLoaded.value ||= open;
});
watch(consent.preferencesOpen, (open) => {
    preferencesLoaded.value ||= open;
});

const overHero = computed(
    () =>
        page.props.site.header.transparent_on_hero &&
        page.props.starts_with_hero === true,
);

const main = useTemplateRef<HTMLElement>('main');
const { navigating, announcement } = useNavigationFeedback(
    main,
    () => page.props.seo.title,
);

// SEO-PERF-012: the motion runtime (and Lenis) loads after first paint and
// idle, only while motion is on (MOT-R03); turning it off at runtime (footer
// toggle, OS setting) reverts every effect, turning it on loads it at once.
const reducedMotion = useReducedMotion();
const motionOptions = () => ({
    smoothScroll: page.props.ui.motion.smooth_scroll,
});

onMounted(() => {
    // FR-LEAD-001: remember the landing page's UTM values for the lead forms.
    captureAttribution();
    consent.markMounted();
    toasterLoaded.value = true;
    startMotion(motionOptions());
});

watch(reducedMotion, (reduced) => {
    if (reduced) {
        stopMotion();
    } else {
        startMotion(motionOptions(), true);
    }
});

// From 640 px toasts sit bottom-right: keep them above the mobile bar
// (640–1023 px) and the compare bar (FR-SHORT-003).
const barShown = ref(false);
const compareShown = ref(false);
const belowLg = useMediaQuery('(max-width: 1023px)');
const toastOffset = computed(() => {
    const bottom =
        (barShown.value && belowLg.value ? 88 : 24) +
        (compareShown.value ? 112 : 0);

    return bottom === 24 ? 24 : { bottom, right: 24 };
});
</script>

<template>
    <TooltipProvider>
        <div class="flex min-h-svh flex-col">
            <SeoHead />
            <SkipLink />
            <ConsentBanner />
            <SiteHeader :over-hero="overHero" />
            <main
                id="content"
                ref="main"
                tabindex="-1"
                :class="[
                    'flex-1 outline-none',
                    !overHero && 'pt-16 lg:pt-20',
                    navigating && 'is-navigating',
                ]"
            >
                <slot />
            </main>
            <SiteFooter />
            <MobileContactBar v-model:shown="barShown" />
            <CompareBar
                v-model:shown="compareShown"
                :lifted="barShown && belowLg"
            />
            <CallbackDialog v-if="callbackLoaded" />
            <ConsentPreferences v-if="preferencesLoaded" />
            <p class="sr-only" aria-live="polite" aria-atomic="true">
                {{ announcement }}
            </p>
            <Toaster v-if="toasterLoaded" :offset="toastOffset" />
        </div>
    </TooltipProvider>
</template>
