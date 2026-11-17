<script setup lang="ts">
import { ChevronDown, ChevronUp, GripVertical, Trash2 } from '@lucide/vue';
import { useId } from 'vue';
import ErrorBadge from '@/components/admin/ErrorBadge.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

// CMP-ADM-FORM repeater card (design/admin.md §12.4): a collapsible card with a drag handle, a
// summary of its content ("Type A · 1,850 sft · 3 bed"), the red error count (VAL-07, the card
// opens itself when it has errors — the parent does that through `open`), Move up / Move down for
// keyboards and a Remove button. The drag handle is `[data-drag-handle]` for the parent's VueDraggable.
const props = defineProps<{
    title: string;
    summary?: string;
    errors?: number;
    /** What the screen reader hears on the buttons, e.g. "unit type 2". */
    noun: string;
    first?: boolean;
    last?: boolean;
    sortable?: boolean;
    disabled?: boolean;
}>();

const emit = defineEmits<{ remove: []; move: [delta: -1 | 1] }>();

const open = defineModel<boolean>('open', { default: false });
const bodyId = useId();
</script>

<template>
    <section
        :class="
            cn(
                'rounded-xl border bg-background text-card-foreground',
                (errors ?? 0) > 0 && 'border-destructive/60',
            )
        "
        :aria-label="`${noun}: ${title}`"
    >
        <div class="flex items-center gap-1 p-2">
            <span
                v-if="sortable && !disabled"
                data-drag-handle
                class="grid size-9 shrink-0 cursor-grab place-items-center rounded-md text-muted-foreground hover:bg-muted active:cursor-grabbing"
                aria-hidden="true"
                title="Drag to reorder"
            >
                <GripVertical class="size-4" />
            </span>

            <button
                type="button"
                class="flex min-h-9 min-w-0 flex-1 items-center gap-3 rounded-md px-2 py-1 text-left outline-offset-2 hover:bg-muted/60"
                :aria-expanded="open"
                :aria-controls="bodyId"
                @click="open = !open"
            >
                <ChevronDown
                    :class="
                        cn(
                            'size-4 shrink-0 text-muted-foreground transition-transform',
                            open ? 'rotate-0' : '-rotate-90',
                        )
                    "
                    aria-hidden="true"
                />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-medium">{{
                        title
                    }}</span>
                    <span
                        v-if="summary"
                        class="block truncate text-xs text-muted-foreground"
                        >{{ summary }}</span
                    >
                </span>
                <ErrorBadge :count="errors ?? 0" />
            </button>

            <template v-if="sortable && !disabled">
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="first"
                    :aria-label="`Move ${noun} up`"
                    @click="emit('move', -1)"
                >
                    <ChevronUp aria-hidden="true" />
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    :disabled="last"
                    :aria-label="`Move ${noun} down`"
                    @click="emit('move', 1)"
                >
                    <ChevronDown aria-hidden="true" />
                </Button>
            </template>
            <Button
                v-if="!disabled"
                type="button"
                variant="ghost"
                size="icon-sm"
                class="text-destructive hover:text-destructive"
                :aria-label="`Remove ${noun}`"
                @click="emit('remove')"
            >
                <Trash2 aria-hidden="true" />
            </Button>
        </div>

        <div v-show="open" :id="bodyId" class="border-t p-4 md:p-5">
            <slot />
        </div>
    </section>
</template>
