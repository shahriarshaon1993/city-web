<script setup lang="ts">
import { computed, useId } from 'vue';
import { cn } from '@/lib/utils';
import type { BlockBackground, BlockSpacing, PageBlock } from '@/types/blocks';

// CMS-PB-001 envelope for every block except the hero: `background`,
// `spacing`, `anchor_id`, `animate`. Dark backgrounds switch to the dark token
// scope (design/foundations.md §2.3). The heading is the H1 only on the first
// block, otherwise an H2 (CMS-PB-004); the slot gets the tag and its id.
const props = withDefaults(
    defineProps<{
        block: PageBlock;
        first: boolean;
        /** False when the block has no heading: the section stays unnamed. */
        labelled?: boolean;
        width?: 'content' | 'wide';
        class?: string;
    }>(),
    { labelled: true, width: 'content', class: undefined },
);

const headingId = useId();
const headingTag = computed(() => (props.first ? 'h1' : 'h2'));

const backgrounds: Record<BlockBackground, string> = {
    default: 'bg-background',
    muted: 'bg-surface-subtle',
    brand: 'bg-surface-brand',
    dark: 'theme-dark',
    gradient: 'theme-dark bg-(image:--gradient-dusk)',
};

const spacings: Record<BlockSpacing, string> = {
    none: '',
    sm: 'py-(--section-sm)',
    md: 'py-(--section-md)',
    lg: 'py-(--section-lg)',
};
</script>

<template>
    <section
        :id="block.anchor_id ?? undefined"
        :aria-labelledby="labelled ? headingId : undefined"
        :data-block="block.type"
        :data-animate="block.animate ? '' : undefined"
        :class="
            cn(
                'scroll-mt-20',
                backgrounds[block.background],
                spacings[block.spacing],
            )
        "
    >
        <div
            :class="
                cn(
                    'mx-auto w-full px-4 sm:px-6 lg:px-8 xl:px-10',
                    width === 'wide' ? 'max-w-wide' : 'max-w-content',
                    props.class,
                )
            "
        >
            <slot :heading-tag="headingTag" :heading-id="headingId" />
        </div>
    </section>
</template>
