<script setup lang="ts">
import type { TocItem } from '@/types/site';

// LAY-LEGAL table of contents: one numbered in-page link per H2 of the body
// (anchors are handled by the smooth-scroll runtime and `scroll-padding-top`).
defineProps<{
    items: TocItem[];
}>();
</script>

<template>
    <ol class="flex flex-col divide-y divide-border border-y border-border">
        <li v-for="(item, index) in items" :key="item.id">
            <a
                :href="`#${item.id}`"
                class="flex min-h-11 items-baseline gap-4 py-3 text-sm text-pretty text-muted-foreground transition-colors hover:text-primary focus-visible:text-primary"
            >
                <span class="text-overline text-primary" aria-hidden="true">
                    {{ String(index + 1).padStart(2, '0') }}
                </span>
                <span>{{ item.label }}</span>
            </a>
        </li>
    </ol>
</template>
