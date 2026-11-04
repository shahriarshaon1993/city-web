<script setup lang="ts">
import { X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { DataTableBulkAction } from '@/types';

// ADM-TBL-008/009: sticky bar that slides up (MOT-SLIDE-UP, 280 ms, transform + opacity only)
// while at least one row is selected. "Select all {total}" switches to selection by filter.
defineProps<{
    count: number;
    total: number;
    pageSelected: boolean;
    allMatching: boolean;
    actions: DataTableBulkAction[];
}>();

const emit = defineEmits<{
    selectAll: [];
    clear: [];
    run: [action: DataTableBulkAction];
}>();
</script>

<template>
    <Transition
        enter-active-class="transition duration-[280ms] ease-(--ease-out-expo) motion-reduce:transition-none"
        leave-active-class="transition duration-[180ms] ease-(--ease-standard) motion-reduce:transition-none"
        enter-from-class="translate-y-4 opacity-0 motion-reduce:translate-y-0"
        leave-to-class="translate-y-4 opacity-0 motion-reduce:translate-y-0"
    >
        <div
            v-if="count > 0"
            role="region"
            aria-label="Bulk actions"
            class="sticky bottom-6 z-(--z-raised) mx-auto mt-4 flex w-fit max-w-full flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border bg-popover px-4 py-3 text-popover-foreground shadow-lg"
        >
            <p class="text-sm font-medium tabular-nums" role="status">
                <template v-if="allMatching"
                    >All {{ total }} matching rows selected</template
                >
                <template v-else>{{ count }} selected</template>
            </p>

            <Button
                v-if="!allMatching && pageSelected && total > count"
                type="button"
                variant="link"
                size="sm"
                class="h-auto p-0"
                @click="emit('selectAll')"
            >
                Select all {{ total }}
            </Button>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="action in actions"
                    :key="action.id"
                    type="button"
                    size="sm"
                    :variant="action.destructive ? 'destructive' : 'outline'"
                    @click="emit('run', action)"
                >
                    <component
                        :is="action.icon"
                        v-if="action.icon"
                        aria-hidden="true"
                    />
                    {{ action.label }}
                </Button>
            </div>

            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                aria-label="Clear selection"
                @click="emit('clear')"
            >
                <X aria-hidden="true" />
            </Button>
        </div>
    </Transition>
</template>
