<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import BlockLinkButton from '@/components/blocks/BlockLinkButton.vue';
import type { BlockLink } from '@/types/blocks';

// Block heading row: H1/H2 (from BlockSection), optional "View all" link and
// extra controls (`actions`, e.g. carousel arrows) on the right.
defineProps<{
    headingTag: string;
    headingId: string;
    heading: string | null;
    viewAll?: BlockLink | null;
}>();
</script>

<template>
    <div
        v-if="heading || viewAll || $slots.actions"
        class="mb-8 flex flex-wrap items-end justify-between gap-x-6 gap-y-3 lg:mb-12"
    >
        <component
            :is="headingTag"
            v-if="heading"
            :id="headingId"
            class="max-w-3xl text-h2 text-balance"
        >
            {{ heading }}
        </component>
        <div class="flex items-center gap-2">
            <BlockLinkButton
                v-if="viewAll"
                :link="viewAll"
                variant="link"
                class="px-0"
            >
                {{ viewAll.label }}
                <ArrowRight class="size-4" aria-hidden="true" />
            </BlockLinkButton>
            <slot name="actions" />
        </div>
    </div>
</template>
