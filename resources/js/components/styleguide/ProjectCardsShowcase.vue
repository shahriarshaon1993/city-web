<script setup lang="ts">
import { computed } from 'vue';
import ProjectCard from '@/components/site/ProjectCard.vue';
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import type { ProjectCard as ProjectCardData } from '@/types/site';

// CMP-CARD-01, every variant in the grids it ships in.
const props = defineProps<{ projects: ProjectCardData[] }>();

const first = computed(() => props.projects[0]);
const second = computed(() => props.projects[1]);
const horizontal = computed(() => props.projects.slice(2, 4));
const mini = computed(() => props.projects.slice(4));
</script>

<template>
    <StyleguideSection
        id="sg-cards"
        title="Project cards"
        description="Default (all demo projects; three hide their price), featured (spans 2 columns ≥ 1280), horizontal and mini."
    >
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-8">
            <li v-for="project in projects" :key="project.id">
                <ProjectCard :project="project" />
            </li>
        </ul>
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-8">
            <li v-if="first" class="xl:col-span-2">
                <ProjectCard :project="first" variant="featured" />
            </li>
            <li v-if="second">
                <ProjectCard :project="second" />
            </li>
        </ul>
        <div class="grid gap-6 lg:grid-cols-2 xl:gap-8">
            <ProjectCard
                v-for="project in horizontal"
                :key="project.id"
                :project="project"
                variant="horizontal"
            />
        </div>
        <ul class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            <li v-for="project in mini" :key="project.id">
                <ProjectCard :project="project" variant="mini" />
            </li>
        </ul>
    </StyleguideSection>
</template>
