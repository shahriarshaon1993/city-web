<script setup lang="ts">
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ProjectGrid from '@/components/blocks/ProjectGrid.vue';
import type { ProjectGridBlock } from '@/types/blocks';

// CMP-BLK-GRID: CMP-CARD-01 grid of the preset's projects (segment, status,
// area resolved server-side), optional "View all" to the matching listing.
defineProps<{
    block: ProjectGridBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-if="block.data.projects.length > 0"
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
            :view-all="block.data.view_all"
        />
        <ProjectGrid :projects="block.data.projects" />
    </BlockSection>
</template>
