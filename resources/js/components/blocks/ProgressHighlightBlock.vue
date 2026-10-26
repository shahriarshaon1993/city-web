<script setup lang="ts">
import BlockHeader from '@/components/blocks/BlockHeader.vue';
import BlockSection from '@/components/blocks/BlockSection.vue';
import ProgressSummary from '@/components/site/ProgressSummary.vue';
import type { ProgressHighlightBlock } from '@/types/blocks';

// CMP-BLK-PROGRESS: 1–3 CMP-PROG-01 summaries with photo, as hairline-
// separated editorial rows (DEC-48) rather than another card grid.
defineProps<{
    block: ProgressHighlightBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection
        v-if="block.data.items.length > 0"
        v-slot="{ headingTag, headingId }"
        :block="block"
        :first="first"
    >
        <BlockHeader
            :heading-tag="headingTag"
            :heading-id="headingId"
            :heading="block.data.heading"
        />
        <ul v-reveal.stagger class="flex flex-col divide-y divide-border">
            <li
                v-for="summary in block.data.items"
                :key="summary.project.id"
                class="py-10 first:pt-0 last:pb-0 lg:py-12"
            >
                <ProgressSummary
                    :summary="summary"
                    :stages="block.data.stages"
                    :heading-as="first ? 'h2' : 'h3'"
                />
            </li>
        </ul>
    </BlockSection>
</template>
