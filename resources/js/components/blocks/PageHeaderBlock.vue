<script setup lang="ts">
import BlockSection from '@/components/blocks/BlockSection.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { PageHeaderBlock } from '@/types/blocks';

// CMP-BLK-PAGEHEAD. Without a photo: breadcrumb, light H1 in 7 columns and the
// intro in 5 beside it, on a bottom hairline. With a photo: the area-page hero
// pattern (direction.md §5) — 60svh under the transparent header, white
// breadcrumb, scrim, MOT-PARALLAX ≤ 8 % — so the page's own `starts_with_hero`
// prop must be set. As the first block it holds the LCP image (eager, SEO-PERF-006)
// and the H1 (CMS-PB-004); further down it is lazy with an H2. `data-hero`
// tells the mobile contact bar to wait until it has scrolled past.
defineProps<{
    block: PageHeaderBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-if="block.data.image"
        v-slot="{ headingTag, headingId }"
        v-parallax="8"
        :block="block"
        :first="first"
        :data-hero="first ? '' : undefined"
        section-class="theme-dark relative isolate flex min-h-[max(50svh,26rem)] flex-col overflow-hidden bg-stone-950 [--overlay:0.7] md:min-h-[max(60svh,32rem)]"
        class="flex flex-1 flex-col justify-end gap-8 pt-28 pb-10 lg:pt-32 lg:pb-16"
    >
        <div data-parallax-layer class="absolute inset-x-0 -inset-y-[8%] -z-10">
            <ResponsiveImage
                :image="block.data.image"
                preset="hero"
                :priority="first"
                :loading="first ? undefined : 'lazy'"
                class="absolute inset-0"
            />
            <div
                data-overlay
                class="absolute inset-0 bg-(image:--gradient-hero-bottom)"
                aria-hidden="true"
            />
        </div>

        <Breadcrumbs
            v-if="block.data.breadcrumbs"
            :items="block.data.breadcrumbs"
            tone="media"
        />
        <div
            v-reveal.hero
            class="flex max-w-4xl flex-col gap-4 [text-shadow:0_1px_2px_rgb(0_0_0/0.35)]"
        >
            <component
                :is="headingTag"
                :id="headingId"
                data-hero-line
                class="font-display text-h1 text-balance text-white"
            >
                {{ block.data.title }}
            </component>
            <p
                v-if="block.data.intro"
                data-hero-line
                class="max-w-prose text-lead text-pretty text-white/90"
            >
                {{ block.data.intro }}
            </p>
        </div>
    </BlockSection>

    <BlockSection
        v-else
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
    >
        <Breadcrumbs
            v-if="block.data.breadcrumbs"
            :items="block.data.breadcrumbs"
            class="mb-8 lg:mb-12"
        />
        <div
            v-reveal.hero
            class="grid gap-x-8 gap-y-6 border-b border-border pb-10 lg:grid-cols-12 lg:items-end lg:pb-14"
        >
            <component
                :is="headingTag"
                :id="headingId"
                data-hero-line
                class="text-h1 text-balance lg:col-span-7"
            >
                {{ block.data.title }}
            </component>
            <p
                v-if="block.data.intro"
                data-hero-line
                class="max-w-prose text-lead text-pretty text-muted-foreground lg:col-span-5 lg:col-start-8"
            >
                {{ block.data.intro }}
            </p>
        </div>
    </BlockSection>
</template>
