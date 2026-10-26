<script setup lang="ts">
import BadgesShowcase from '@/components/styleguide/BadgesShowcase.vue';
import ButtonsShowcase from '@/components/styleguide/ButtonsShowcase.vue';
import ColourScales from '@/components/styleguide/ColourScales.vue';
import EmptySkeletonShowcase from '@/components/styleguide/EmptySkeletonShowcase.vue';
import FormattersTable from '@/components/styleguide/FormattersTable.vue';
import FormControlsPanel from '@/components/styleguide/FormControlsPanel.vue';
import ImagesShowcase from '@/components/styleguide/ImagesShowcase.vue';
import MotionShowcase from '@/components/styleguide/MotionShowcase.vue';
import ProjectCardsShowcase from '@/components/styleguide/ProjectCardsShowcase.vue';
import SemanticTokens from '@/components/styleguide/SemanticTokens.vue';
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import TabsAccordionShowcase from '@/components/styleguide/TabsAccordionShowcase.vue';
import ToastsShowcase from '@/components/styleguide/ToastsShowcase.vue';
import { themes } from '@/components/styleguide/tokens';
import TypeScale from '@/components/styleguide/TypeScale.vue';
import type {
    FormatSample,
    StyleguideImages,
} from '@/components/styleguide/types';
import type { ProjectCard as ProjectCardData } from '@/types/site';

// Local-only UI kit reference (phases 0.2–0.3). Sample copy here is developer
// tooling, not site content, so it is not props-driven. One component per
// section in components/styleguide.
defineProps<{
    images: StyleguideImages;
    projects: ProjectCardData[];
    ogImage: string | null;
    formatSamples: FormatSample[];
}>();
</script>

<template>
    <div class="bg-background">
        <div class="theme-dark bg-(image:--gradient-dusk)">
            <div
                class="mx-auto max-w-content px-4 py-12 sm:px-6 lg:px-8 xl:px-10"
            >
                <p class="text-overline text-primary uppercase">
                    Phases 0.2–0.3 · local only
                </p>
                <h1 class="mt-2 font-display text-h1">UI kit &amp; tokens</h1>
                <p class="mt-3 max-w-prose text-lead text-muted-foreground">
                    design/foundations.md §2–§5, §13 and CMP-BTN, BADGE, FORM,
                    TABS, ACC, TOAST, EMPTY, SKEL, CARD; images and formatters
                    (ARC-016, ARC-017).
                </p>
            </div>
        </div>

        <div
            class="mx-auto flex max-w-content flex-col gap-16 px-4 py-12 sm:px-6 lg:px-8 xl:px-10"
        >
            <ColourScales />
            <SemanticTokens />
            <TypeScale />
            <ButtonsShowcase />
            <BadgesShowcase />
            <StyleguideSection id="sg-controls" title="Form controls">
                <div class="grid gap-6 xl:grid-cols-2">
                    <FormControlsPanel
                        v-for="theme in themes"
                        :key="theme"
                        :theme="theme"
                    />
                </div>
            </StyleguideSection>
            <TabsAccordionShowcase />
            <ToastsShowcase />
            <EmptySkeletonShowcase />
            <ImagesShowcase :images="images" :og-image="ogImage" />
            <ProjectCardsShowcase :projects="projects" />
            <MotionShowcase :images="images" />
            <FormattersTable :samples="formatSamples" />
        </div>
    </div>
</template>
