<script setup lang="ts">
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { formatNumber } from '@/lib/format';
import type { AreaDetail } from '@/types/location';

// LAY-LOC-DETAIL area hero: the area photo (the page's LCP image) at 60svh
// (50svh on phones) under the transparent header, bottom scrim, white
// breadcrumb, overline "City · n projects" (direction.md: overlines, not
// chips) and the light H1. MOT-PARALLAX ≤ 8 % and the hero text reveal as
// on the home hero (client visits only, DEC-50). `data-hero` tells the
// mobile contact bar to wait until it has scrolled past.
defineProps<{
    area: AreaDetail;
}>();
</script>

<template>
    <section
        v-parallax="8"
        data-hero
        class="relative isolate flex min-h-[max(50svh,26rem)] flex-col overflow-hidden bg-stone-950 [--overlay:0.7] md:min-h-[max(60svh,32rem)]"
    >
        <div data-parallax-layer class="absolute inset-x-0 -inset-y-[8%] -z-10">
            <ResponsiveImage
                :image="area.hero"
                preset="hero"
                priority
                class="absolute inset-0"
            />
            <div
                data-overlay
                class="absolute inset-0 bg-(image:--gradient-hero-bottom)"
                aria-hidden="true"
            />
        </div>

        <div
            class="mx-auto flex w-full max-w-content flex-1 flex-col justify-end gap-8 px-4 pt-28 pb-10 sm:px-6 lg:px-8 lg:pt-32 lg:pb-16 xl:px-10"
        >
            <Breadcrumbs :items="area.breadcrumbs" tone="media" />
            <div
                v-reveal.hero
                class="flex max-w-4xl flex-col gap-4 [text-shadow:0_1px_2px_rgb(0_0_0/0.35)]"
            >
                <p data-hero-line class="text-overline text-white/80 uppercase">
                    {{ area.city }} ·
                    {{ formatNumber(area.project_count) }}
                    {{ area.project_count === 1 ? 'project' : 'projects' }}
                </p>
                <h1
                    data-hero-line
                    class="font-display text-h1 text-balance text-white"
                >
                    {{ area.title }}
                </h1>
            </div>
        </div>
    </section>
</template>
