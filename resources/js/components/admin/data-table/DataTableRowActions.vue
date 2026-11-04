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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { DataTableRowAction } from '@/types';

// CMP-ADM-TABLE row actions: the first two as icon buttons with tooltips, the rest in a ⋯ menu.
// HELP-08: an action that is not possible right now stays visible, disabled, with the reason.
const props = defineProps<{
    actions: DataTableRowAction[];
    rowLabel: string;
}>();

const visible = computed(() =>
    props.actions.filter((action) => !action.hidden),
);
const inline = computed(() => visible.value.slice(0, 2));
const more = computed(() => visible.value.slice(2));

function run(action: DataTableRowAction): void {
    if (!action.disabledReason) {
        action.onSelect();
    }
}
</script>

<template>
    <div class="flex items-center justify-end gap-0.5">
        <Tooltip v-for="action in inline" :key="action.id">
            <TooltipTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :class="action.destructive ? 'text-destructive' : ''"
                    :aria-label="`${action.label}: ${rowLabel}`"
                    :aria-disabled="action.disabledReason ? 'true' : undefined"
                    @click.stop="run(action)"
                >
                    <component
                        :is="action.icon"
                        v-if="action.icon"
                        aria-hidden="true"
                    />
                    <span v-else class="text-xs">{{ action.label }}</span>
                </Button>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-64">
                {{ action.disabledReason ?? action.label }}
            </TooltipContent>
        </Tooltip>

        <DropdownMenu v-if="more.length">
            <DropdownMenuTrigger as-child>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :aria-label="`More actions: ${rowLabel}`"
                    @click.stop
                >
                    <Ellipsis aria-hidden="true" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-56">
                <template v-for="(action, index) in more" :key="action.id">
                    <DropdownMenuSeparator
                        v-if="action.destructive && index > 0"
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
