<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';

// Prev/next pair for the block heading row (useCarousel, DEC-44).
defineProps<{
    canPrevious: boolean;
    canNext: boolean;
    previousLabel: string;
    nextLabel: string;
}>();

defineEmits<{
    scroll: [direction: 'previous' | 'next'];
}>();
</script>

<template>
    <div class="flex items-center gap-2">
        <Button
            type="button"
            variant="outline"
            size="icon"
            class="rounded-full"
            :aria-label="previousLabel"
            :disabled="!canPrevious"
            @click="$emit('scroll', 'previous')"
        >
            <ChevronLeft class="size-5" aria-hidden="true" />
        </Button>
        <Button
            type="button"
            variant="outline"
            size="icon"
            class="rounded-full"
            :aria-label="nextLabel"
            :disabled="!canNext"
            @click="$emit('scroll', 'next')"
        >
            <ChevronRight class="size-5" aria-hidden="true" />
        </Button>
    </div>
</template>
