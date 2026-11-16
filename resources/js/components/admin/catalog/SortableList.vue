<script setup lang="ts" generic="T extends { id: number }">
import { ChevronDown, ChevronUp, GripVertical } from '@lucide/vue';
import { nextTick, ref } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

// CMP-ADM-SORTLIST / CRUD-06: a reorderable list. Drag the grip, or use the keyboard: Space on the
// grip lifts the row, ↑ ↓ move it, Space drops it (Esc puts it back), with live announcements.
// "Move up / Move down" buttons give the same without dragging. A finished move emits `reorder`
// with the ids in their new order, and the parent saves it at once ("Order saved" + Undo).
const props = defineProps<{
    /** Accessible name of the list. */
    label: string;
    /** Names a row for the screen reader ("Bashundhara R/A"). */
    itemLabel: (item: T) => string;
    disabled?: boolean;
    /** Rows of other lists with the same group name may be dragged here; leave out to keep lists apart. */
    tag?: 'ol' | 'ul';
    compact?: boolean;
    /** Rows with tall content keep the grip and arrows at the top. */
    alignTop?: boolean;
}>();

const emit = defineEmits<{ reorder: [ids: number[]] }>();
const items = defineModel<T[]>({ required: true });

const lifted = ref<number | null>(null);
const announcement = ref('');
let snapshot: number[] = [];
let ids: number[] = [];

function say(message: string): void {
    announcement.value = '';
    void nextTick(() => {
        announcement.value = message;
    });
}

function onStart(): void {
    ids = items.value.map((item) => item.id);
}

function onEnd(): void {
    const next = items.value.map((item) => item.id);

    if (next.join() !== ids.join()) {
        emit('reorder', next);
    }
}

function move(index: number, delta: -1 | 1): void {
    const target = index + delta;

    if (target < 0 || target >= items.value.length) {
        return;
    }

    const next = [...items.value];
    [next[index], next[target]] = [next[target], next[index]];
    items.value = next;
}

async function focusHandle(id: number): Promise<void> {
    await nextTick();
    document.querySelector<HTMLElement>(`[data-handle-id="${id}"]`)?.focus();
}

function onHandleKey(event: KeyboardEvent, item: T, index: number): void {
    const label = props.itemLabel(item);

    if (event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();

        if (lifted.value === item.id) {
            lifted.value = null;
            say(
                `${label} dropped at position ${index + 1} of ${items.value.length}.`,
            );

            if (items.value.map((row) => row.id).join() !== snapshot.join()) {
                emit(
                    'reorder',
                    items.value.map((row) => row.id),
                );
            }
        } else {
            lifted.value = item.id;
            snapshot = items.value.map((row) => row.id);
            say(
                `${label} lifted. Use the up and down arrow keys to move it, Space to drop.`,
            );
        }

        return;
    }

    if (lifted.value !== item.id) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        const order = new Map(snapshot.map((id, position) => [id, position]));
        items.value = [...items.value].sort(
            (a, b) => (order.get(a.id) ?? 0) - (order.get(b.id) ?? 0),
        );
        lifted.value = null;
        say(`Move cancelled. ${label} is back at its place.`);

        return;
    }

    if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
        event.preventDefault();
        const delta = event.key === 'ArrowUp' ? -1 : 1;
        move(index, delta);
        say(
            `${label}, position ${Math.min(Math.max(index + delta + 1, 1), items.value.length)} of ${items.value.length}.`,
        );
        void focusHandle(item.id);
    }
}

function step(index: number, delta: -1 | 1, item: T): void {
    move(index, delta);
    emit(
        'reorder',
        items.value.map((row) => row.id),
    );
    say(
        `${props.itemLabel(item)} moved to position ${index + delta + 1} of ${items.value.length}.`,
    );
}
</script>

<template>
    <div>
        <VueDraggable
            v-model="items"
            :tag="tag ?? 'ol'"
            handle="[data-drag-handle]"
            :animation="150"
            ghost-class="opacity-40"
            :disabled="disabled"
            :aria-label="label"
            class="grid gap-2"
            @start="onStart"
            @end="onEnd"
        >
            <li
                v-for="(item, index) in items"
                :key="item.id"
                :class="
                    cn(
                        'flex gap-3 rounded-lg border bg-card text-card-foreground',
                        alignTop ? 'items-start' : 'items-center',
                        compact ? 'p-2' : 'p-3',
                        lifted === item.id && 'ring-2 ring-ring',
                    )
                "
            >
                <button
                    v-if="!disabled"
                    type="button"
                    data-drag-handle
                    :data-handle-id="item.id"
                    class="grid size-9 shrink-0 cursor-grab place-items-center rounded-md text-muted-foreground hover:bg-muted active:cursor-grabbing"
                    :aria-label="`Move ${itemLabel(item)}. Press Space to lift it, then the arrow keys.`"
                    :aria-pressed="lifted === item.id"
                    @keydown="onHandleKey($event, item, index)"
                >
                    <GripVertical class="size-4" aria-hidden="true" />
                </button>

                <div class="min-w-0 flex-1">
                    <slot :item="item" :index="index" />
                </div>

                <div class="flex shrink-0 items-center gap-1">
                    <slot name="actions" :item="item" :index="index" />
                    <template v-if="!disabled">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :disabled="index === 0"
                            :aria-label="`Move ${itemLabel(item)} up`"
                            @click="step(index, -1, item)"
                        >
                            <ChevronUp aria-hidden="true" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            :disabled="index === items.length - 1"
                            :aria-label="`Move ${itemLabel(item)} down`"
                            @click="step(index, 1, item)"
                        >
                            <ChevronDown aria-hidden="true" />
                        </Button>
                    </template>
                </div>
            </li>
        </VueDraggable>

        <p class="sr-only" role="status" aria-live="polite">
            {{ announcement }}
        </p>
    </div>
</template>
