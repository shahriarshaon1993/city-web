<script setup lang="ts">
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import type { ProjectHighlight } from '@/types/project';

// PG-04 Overview: the rich text (sanitised HTML from the server, its first
// paragraph as a lead) and the feature highlights as alternating
// photo / text rows (LAY-PROJ-DETAIL "image_text-like alternating").
defineProps<{
    overview: string | null;
    narrative: string | null;
    highlights: ProjectHighlight[];
}>();
</script>

<template>
    <div class="flex flex-col gap-12 lg:gap-16">
        <div
            v-if="overview"
            v-reveal
            class="flex max-w-prose flex-col gap-4 text-body-lg text-pretty text-muted-foreground [&>p:first-child]:text-lead [&>p:first-child]:text-foreground"
            v-html="overview"
        />
        <div
            v-if="narrative"
            v-reveal
            class="flex max-w-prose flex-col gap-4 text-body text-pretty text-muted-foreground"
            v-html="narrative"
        />

        <ul v-if="highlights.length > 0" class="flex flex-col gap-10 lg:gap-14">
            <li
                v-for="(highlight, index) in highlights"
                :key="highlight.id"
                v-reveal
                class="grid items-center gap-6 sm:grid-cols-2 sm:gap-8"
            >
                <ResponsiveImage
                    :image="highlight.image"
                    preset="project-highlight"
                    :class="['rounded-xl', index % 2 === 1 && 'sm:order-2']"
                />
                <div class="flex flex-col gap-3">
                    <p
                        class="tabular text-overline text-muted-foreground uppercase"
                        aria-hidden="true"
                    >
                        {{ String(index + 1).padStart(2, '0') }}
                    </p>
                    <h3 class="font-display text-h4 text-balance">
                        {{ highlight.title }}
                    </h3>
                    <p
                        class="max-w-md text-body text-pretty text-muted-foreground"
                    >
                        {{ highlight.text }}
                    </p>
                </div>
            </li>
        </ul>
    </div>
</template>
