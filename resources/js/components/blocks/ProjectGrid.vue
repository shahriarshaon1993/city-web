<script setup lang="ts">
import ProjectCard from '@/components/site/ProjectCard.vue';
import type { ProjectCard as ProjectCardData } from '@/types/site';

// CMP-CARD-01 responsive grid: 1 col < 640, 2 cols 640–1023, 3 cols ≥ 1024.
defineProps<{
    projects: ProjectCardData[];
}>();
</script>

<template>
    <ul
        v-reveal.stagger
        class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:gap-8"
    >
        <li v-for="project in projects" :key="project.id">
            <ProjectCard :project="project" />
        </li>
    </ul>
</template>
