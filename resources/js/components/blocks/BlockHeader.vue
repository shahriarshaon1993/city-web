<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import BlockLinkButton from '@/components/blocks/BlockLinkButton.vue';
import type { BlockLink } from '@/types/blocks';

// Block heading row: H1/H2 (from BlockSection), optional "View all" link and
// extra controls (`actions`, e.g. carousel arrows) on the right, over a
// hairline (DEC-46, Rancon).
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
        class="mb-8 flex flex-wrap items-end justify-between gap-x-6 gap-y-4 border-b border-border pb-6 lg:mb-12 lg:pb-8"
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
                class="group/view px-0 text-foreground"
            >
                {{ viewAll.label }}
                <ArrowRight
                    class="size-4 transition-transform duration-(--dur-base) group-hover/view:translate-x-1"
                    aria-hidden="true"
                />
            </BlockLinkButton>
            <slot name="actions" />
        </div>
    </div>
</template>
