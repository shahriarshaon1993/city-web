<script setup lang="ts">
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ProjectVideo from '@/components/site/project/ProjectVideo.vue';
import type { VideoBlock } from '@/types/blocks';

// CMP-BLK-VIDEO: the CMP-VIDEO-01 facade (poster, glass play button, title
// over the scrim) at content width, caption below. Nothing loads from
// YouTube before the click (SEO-PERF-013); uploads play natively with their
// WebVTT track. The component is shared with the project detail page.
defineProps<{
    block: VideoBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
        :labelled="block.data.heading !== null"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        />
        <div v-reveal class="flex flex-col gap-4">
            <ProjectVideo
                :video="block.data.video"
                :tour-url="null"
                tour-label=""
            />
            <p
                v-if="block.data.caption"
                class="max-w-prose text-sm text-pretty text-muted-foreground"
            >
                {{ block.data.caption }}
            </p>
        </div>
    </BlockSection>
</template>
