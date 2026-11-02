<script setup lang="ts">
import BlockSection from '@/components/blocks/BlockSection.vue';
import ProjectFaq from '@/components/site/project/ProjectFaq.vue';
import { cn } from '@/lib/utils';
import type { FaqBlock } from '@/types/blocks';

// CMP-BLK-FAQ / CMP-ACC-01: sticky heading in 4 columns, hairline accordion
// in 8 (the facet-FAQ pattern, direction.md §5). Single-open; the answers are
// in the DOM for crawlers (the FAQPage JSON-LD arrives with 19.1). Without a
// heading the accordion sits centred in 8 columns on its own.
defineProps<{
    block: FaqBlock;
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
        <div v-reveal class="grid gap-8 lg:grid-cols-12 xl:gap-x-8">
            <component
                :is="headingTag"
                v-if="block.data.heading"
                :id="headingId"
                class="font-display text-h2 text-balance lg:sticky lg:top-28 lg:col-span-4 lg:self-start"
            >
                {{ block.data.heading }}
            </component>
            <ProjectFaq
                :items="block.data.items"
                :class="
                    cn('lg:col-span-8', !block.data.heading && 'lg:col-start-3')
                "
            />
        </div>
    </BlockSection>
</template>
