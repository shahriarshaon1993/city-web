<script setup lang="ts">
import { ChevronLeft, ChevronRight, Columns3, Minus, Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import type { DataTableColumn } from '@/types';

// ADM-TBL-005/006/007 column menu: visibility checkboxes (required columns locked), "Move
// left/right" for keyboard reordering and a width stepper. Dragging headers and the header
// resize handle are the pointer shortcuts for the same state.
const props = defineProps<{
    scope: string;
    columns: DataTableColumn[];
    visible: string[];
    widthOf: (id: string) => number;
}>();

const emit = defineEmits<{
    toggle: [id: string];
    move: [id: string, delta: -1 | 1];
    width: [id: string, width: number];
    reset: [];
}>();

const STEP = 20;

/** Visible columns first, in display order, then the hidden ones. */
function ordered(): DataTableColumn[] {
    const byId = new Map(props.columns.map((column) => [column.id, column]));
    const shown = props.visible.flatMap((id) =>
        byId.has(id) ? [byId.get(id)!] : [],
    );

    return [
        ...shown,
        ...props.columns.filter((column) => !props.visible.includes(column.id)),
    ];
}
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" class="font-normal">
                <Columns3 aria-hidden="true" />
                Columns
            </Button>
        </PopoverTrigger>
        <PopoverContent align="end" class="w-[22rem] p-3">
            <p class="mb-2 text-sm font-semibold">Columns</p>
            <ul class="grid gap-0.5">
                <li
                    v-for="(column, index) in ordered()"
                    :key="column.id"
                    class="flex items-center gap-2 rounded-sm px-1.5 py-1 hover:bg-accent"
                >
                    <Checkbox
                        :id="`${scope}-col-${column.id}`"
                        :model-value="visible.includes(column.id)"
                        :disabled="column.required"
                        :aria-describedby="
                            column.required
                                ? `${scope}-col-${column.id}-locked`
                                : undefined
                        "
                        @update:model-value="emit('toggle', column.id)"
                    />
                    <label
                        :for="`${scope}-col-${column.id}`"
                        class="min-w-0 flex-1 truncate text-sm"
                    >
                        {{ column.label }}
                        <span
                            v-if="column.required"
                            :id="`${scope}-col-${column.id}-locked`"
                            class="sr-only"
                        >
                            (always shown)
                        </span>
                    </label>
                    <template v-if="visible.includes(column.id)">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="size-7"
                            :disabled="index === 0"
                            :aria-label="`Move ${column.label} left`"
                            @click="emit('move', column.id, -1)"
                        >
                            <ChevronLeft aria-hidden="true" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            class="size-7"
                            :disabled="index === visible.length - 1"
                            :aria-label="`Move ${column.label} right`"
                            @click="emit('move', column.id, 1)"
                        >
                            <ChevronRight aria-hidden="true" />
                        </Button>
                        <div
                            class="flex items-center"
                            role="group"
                            :aria-label="`Width of ${column.label}`"
                        >
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="size-7"
                                :aria-label="`Make ${column.label} narrower`"
                                @click="
                                    emit(
                                        'width',
                                        column.id,
                                        widthOf(column.id) - STEP,
                                    )
                                "
                            >
                                <Minus aria-hidden="true" />
                            </Button>
                            <span
                                class="w-9 text-center text-xs text-muted-foreground tabular-nums"
                            >
                                {{ widthOf(column.id) }}
                            </span>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon-sm"
                                class="size-7"
                                :aria-label="`Make ${column.label} wider`"
                                @click="
                                    emit(
                                        'width',
                                        column.id,
                                        widthOf(column.id) + STEP,
                                    )
                                "
                            >
                                <Plus aria-hidden="true" />
                            </Button>
                        </div>
                    </template>
                </li>
            </ul>
            <div class="mt-2 border-t pt-2">
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="emit('reset')"
                >
                    Reset columns and widths
                </Button>
            </div>
        </PopoverContent>
    </Popover>
</template>
