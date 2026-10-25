<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SeoHead from '@/components/site/SeoHead.vue';
import SkipLink from '@/components/site/SkipLink.vue';
import SiteHeader from '@/components/site/header/SiteHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import type { SitePageProps } from '@/types/site';

// Persistent public layout (ARC-010), default for every pages/Site/* page
// (app.ts): header state survives navigation. Pages render inside <main>
// and must not add their own. Footer, breadcrumbs and the contact bar: 1.2.
const page = usePage<SitePageProps>();

const overHero = computed(
    () =>
        page.props.site.header.transparent_on_hero &&
        page.props.starts_with_hero === true,
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
                tabindex="-1"
                :class="['flex-1 outline-none', !overHero && 'pt-16 lg:pt-20']"
            >
                <slot />
            </main>
            <Toaster />
        </div>
    </TooltipProvider>
</template>
