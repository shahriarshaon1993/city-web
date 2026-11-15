<script setup lang="ts">
import { Ellipsis } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { DataTableRowAction } from '@/types';

// CMP-ADM-TABLE row actions (DEC-75): every action is an item of one ⋯ menu at the end of the row,
// never an inline icon button. HELP-08: an action that is not possible right now stays in the
// menu, disabled, with the reason under its label. Destructive actions come last, after a separator.
const props = defineProps<{
    actions: DataTableRowAction[];
    rowLabel: string;
}>();

const visible = computed(() =>
    props.actions
        .filter((action) => !action.hidden)
        .sort((a, b) => Number(!!a.destructive) - Number(!!b.destructive)),
);

function run(action: DataTableRowAction): void {
    if (!action.disabledReason) {
        action.onSelect();
    }
}
</script>

<template>
    <div v-if="visible.length" class="flex items-center justify-end">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="`Actions: ${rowLabel}`"
                    @click.stop
                >
                    <Ellipsis aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56" @click.stop>
                <template v-for="(action, index) in visible" :key="action.id">
                    <DropdownMenuSeparator
                        v-if="
                            action.destructive &&
                            index > 0 &&
                            !visible[index - 1].destructive
                        "
                    />
                    <DropdownMenuItem
                        :disabled="!!action.disabledReason"
                        :class="
                            action.destructive
                                ? 'text-destructive focus:text-destructive'
                                : ''
                        "
                        @select="run(action)"
                    >
                        <component
                            :is="action.icon"
                            v-if="action.icon"
                            aria-hidden="true"
                        />
                        <span class="flex flex-col">
                            {{ action.label }}
                            <span
                                v-if="action.disabledReason"
                                class="text-xs font-normal text-muted-foreground"
                            >
                                {{ action.disabledReason }}
                            </span>
                        </span>
                    </DropdownMenuItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
