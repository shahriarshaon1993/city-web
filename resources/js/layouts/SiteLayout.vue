<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { useMediaQuery } from '@vueuse/core';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import MobileContactBar from '@/components/site/MobileContactBar.vue';
import SeoHead from '@/components/site/SeoHead.vue';
import SkipLink from '@/components/site/SkipLink.vue';
import SiteFooter from '@/components/site/footer/SiteFooter.vue';
import SiteHeader from '@/components/site/header/SiteHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useNavigationFeedback } from '@/composables/useNavigationFeedback';
import { startMotion, stopMotion } from '@/motion/bridge';
import { useReducedMotion } from '@/motion/preference';
import type { SitePageProps } from '@/types/site';

// Persistent public layout (ARC-010), default for every pages/Site/* page
// (app.ts): header state survives navigation. Pages render inside <main>
// and must not add their own; breadcrumbs are rendered by the pages.
const page = usePage<SitePageProps>();

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

onMounted(() => startMotion(motionOptions()));

watch(reducedMotion, (reduced) => {
    if (reduced) {
        stopMotion();
    } else {
        startMotion(motionOptions(), true);
    }
});

// Between 640 and 1023 px toasts sit bottom-right: keep them above the bar.
const barShown = ref(false);
const belowLg = useMediaQuery('(max-width: 1023px)');
const toastOffset = computed(() =>
    barShown.value && belowLg.value ? { bottom: 88, right: 24 } : 24,
);
</script>

<template>
    <TooltipProvider>
        <div class="flex min-h-svh flex-col">
            <SeoHead />
            <SkipLink />
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
            <p class="sr-only" aria-live="polite" aria-atomic="true">
                {{ announcement }}
            </p>
            <Toaster :offset="toastOffset" />
        </div>
    </TooltipProvider>
</template>
