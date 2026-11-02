<script setup lang="ts">
import BlockSection from '@/components/blocks/BlockSection.vue';
import { cn } from '@/lib/utils';
import type { RichTextBlock } from '@/types/blocks';

// CMP-BLK-RICH: prose (`.rich-text`, app.css) — `narrow` = 68ch, `wide` = two
// columns from 1024 px. The block has no heading of its own; headings inside
// the body are H2/H3 (CMS-PB-004), so the section is unnamed. The body is
// sanitised server-side (CMS-PB-003).
defineProps<{
    block: RichTextBlock;
    first: boolean;
}>();
</script>

<template>
    <BlockSection :block="block" :first="first" :labelled="false">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div
            v-reveal
            :class="
                cn(
                    'rich-text',
                    block.data.width === 'narrow'
                        ? 'max-w-prose'
                        : 'lg:columns-2 lg:gap-16',
                )
            "
            v-html="block.data.body"
        />
    </BlockSection>
</template>
