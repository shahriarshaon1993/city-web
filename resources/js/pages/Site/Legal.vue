<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import LegalToc from '@/components/site/legal/LegalToc.vue';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import { formatDate } from '@/lib/format';
import type { Breadcrumb, TocItem } from '@/types/site';

// PG-23/24 (LAY-LEGAL): light H1 and "Last updated" on a hairline, then the
// prose body (sanitised rich text, H2s carry the ids the server generated)
// beside a table of contents. From 1280 px the TOC is a sticky left column;
// below it collapses into a native <details> (works without JS). The one
// H1 is the title; the body only has H2/H3.
defineProps<{
    title: string;
    /** ISO date. */
    updated_at: string;
    body: string;
    toc: TocItem[];
    breadcrumbs: Breadcrumb[];
}>();
</script>

<template>
    <div
        class="mx-auto w-full max-w-content px-4 pt-8 pb-(--section-md) sm:px-6 lg:px-8 lg:pt-10 xl:px-10"
    >
        <Breadcrumbs :items="breadcrumbs" class="mb-8 lg:mb-12" />

        <header
            v-reveal.hero
            class="grid gap-x-8 gap-y-4 border-b border-border pb-10 lg:grid-cols-12 lg:items-end lg:pb-14"
        >
            <h1 data-hero-line class="text-h1 text-balance lg:col-span-7">
                {{ title }}
            </h1>
            <p
                data-hero-line
                class="text-overline text-muted-foreground uppercase lg:col-span-5 lg:col-start-8"
            >
                Last updated
                <time :datetime="updated_at" class="text-primary">
                    {{ formatDate(updated_at) }}
                </time>
            </p>
        </header>

        <div
            class="mt-10 grid gap-x-8 gap-y-10 lg:mt-14 xl:grid-cols-12 xl:gap-x-10"
        >
            <details v-if="toc.length > 0" class="group xl:hidden">
                <summary
                    class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-4 border-y border-border py-3 text-overline uppercase [&::-webkit-details-marker]:hidden"
                >
                    On this page
                    <ChevronDown
                        class="size-4 transition-transform group-open:rotate-180"
                        aria-hidden="true"
                    />
                </summary>
                <nav aria-label="On this page" class="pb-2">
                    <LegalToc :items="toc" class="border-t-0" />
                </nav>
            </details>

            <aside v-if="toc.length > 0" class="hidden xl:col-span-3 xl:block">
                <nav
                    aria-label="On this page"
                    class="sticky-under-tabs flex flex-col gap-4"
                >
                    <p class="text-overline text-muted-foreground uppercase">
                        On this page
                    </p>
                    <LegalToc :items="toc" />
                </nav>
            </aside>

            <!-- eslint-disable-next-line vue/no-v-html -->
            <article
                class="rich-text max-w-[68ch] xl:col-span-8 xl:col-start-5"
                v-html="body"
            />
        </div>
    </div>
</template>
