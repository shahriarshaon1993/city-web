<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import { Button } from '@/components/ui/button';

// ADM-TBL-014 error (with retry) and the two empty states (nothing yet / nothing matches),
// shared by the table body and the grid view.
defineProps<{
    state: 'failed' | 'empty';
    noun: string;
    filtered: boolean;
    emptyTitle?: string;
    emptyDescription?: string;
}>();

const emit = defineEmits<{ retry: []; reset: [] }>();
</script>

<template>
    <div
        v-if="state === 'failed'"
        role="alert"
        class="mx-auto grid max-w-sm justify-items-center gap-3 text-center"
    >
        <TriangleAlert class="size-6 text-destructive" aria-hidden="true" />
        <p class="text-sm">
            This table could not be loaded. Check your connection and try again.
        </p>
        <Button type="button" variant="outline" size="sm" @click="emit('retry')"
            >Try again</Button
        >
    </div>

    <div
        v-else
        class="mx-auto grid max-w-sm justify-items-center gap-2 text-center"
    >
        <slot v-if="!filtered" name="icon" />
        <template v-if="filtered">
            <p class="font-medium">No {{ noun }}s match</p>
            <p class="text-sm text-muted-foreground">
                Nothing fits the search and filters you chose.
            </p>
            <Button
                type="button"
                variant="outline"
                size="sm"
                class="mt-1"
                @click="emit('reset')"
            >
                Clear search and filters
            </Button>
        </template>
        <template v-else>
            <p class="font-medium">
                {{ emptyTitle ?? `No ${noun}s yet` }}
            </p>
            <p v-if="emptyDescription" class="text-sm text-muted-foreground">
                {{ emptyDescription }}
            </p>
            <slot />
        </template>
    </div>
</template>
