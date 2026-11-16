<script setup lang="ts">
import {
    ArrowDown,
    ArrowUp,
    ChevronDown,
    ChevronRight,
    GripVertical,
    IndentIncrease,
    IndentDecrease,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import { computed, inject } from 'vue';
import DataTableRowActions from '@/components/admin/data-table/DataTableRowActions.vue';
import ErrorBadge from '@/components/admin/ErrorBadge.vue';
import {
    canAddChild,
    errorsWithin,
    indentReason,
    linkStatus,
    outdentReason,
    ownErrors,
    rowDomId,
} from '@/lib/menu-tree';
import { cn } from '@/lib/utils';
import type { DataTableRowAction, MenuNode } from '@/types';
import { menuTreeContextKey, type MenuTreeContext } from './context';

// One row of the tree: drag handle (also the keyboard handle), label and where it links, flags,
// error count and the ⋯ menu (DEC-75: every row action lives there). Clicking the label edits.
const props = defineProps<{
    node: MenuNode;
    /** Error-key path: `items.2` or `items.2.children.1`. */
    path: string;
    depth: number;
    first: boolean;
    last: boolean;
}>();

const injected = inject(menuTreeContextKey);

if (!injected) {
    throw new Error('MenuTreeRow needs the menu tree context.');
}

const ctx: MenuTreeContext = injected;

const TYPE_LABELS: Record<string, string> = {
    page: 'Page',
    project: 'Project',
    location: 'Area',
    post: 'News article',
    category: 'News category',
    url: 'Web address',
    anchor: 'Section',
};

const status = computed(() => linkStatus(props.node));
const lifted = computed(() => ctx.lifted === props.node.key);
const collapsed = computed(() => ctx.collapsed.includes(props.node.key));
const label = computed(() => props.node.label || 'Untitled item');

const meta = computed(() => {
    const node = props.node;
    const type = TYPE_LABELS[node.link_type];

    if (node.link_type === 'url') {
        return `${type} · ${node.url || '—'}`;
    }

    if (node.link_type === 'anchor') {
        return `${type} · ${node.url || '/'}#${(node.anchor ?? '').replace(/^#/, '')}`;
    }

    return `${type} · ${node.link?.target?.label ?? 'not chosen'}`;
});

const flag = computed(() => {
    if (status.value === 'unpublished') {
        return 'Hidden on the site: not published yet';
    }

    if (status.value === 'missing') {
        return 'Hidden on the site: its target is gone';
    }

    return null;
});

const messages = computed(() => ownErrors(ctx.errors, props.path));
const errorCount = computed(() =>
    collapsed.value
        ? errorsWithin(ctx.errors, props.path)
        : messages.value.length,
);

const addChildReason = computed(() =>
    canAddChild(props.node, props.depth, ctx.limits),
);

const actions = computed<DataTableRowAction[]>(() => [
    {
        id: 'edit',
        label: 'Edit item',
        icon: Pencil,
        onSelect: () => ctx.edit(props.node),
    },
    {
        id: 'add-child',
        label: 'Add sub-item',
        icon: Plus,
        disabledReason: addChildReason.value,
        hidden: ctx.limits.maxDepth < 2,
        onSelect: () => ctx.addChild(props.node),
    },
    {
        id: 'up',
        label: 'Move up',
        icon: ArrowUp,
        disabledReason: props.first ? 'It is already first in its list.' : null,
        onSelect: () => ctx.perform('moveUp', props.node),
    },
    {
        id: 'down',
        label: 'Move down',
        icon: ArrowDown,
        disabledReason: props.last ? 'It is already last in its list.' : null,
        onSelect: () => ctx.perform('moveDown', props.node),
    },
    {
        id: 'indent',
        label: 'Nest under the item above',
        icon: IndentIncrease,
        hidden: ctx.limits.maxDepth < 2,
        disabledReason: indentReason(ctx.roots, props.node.key, ctx.limits),
        onSelect: () => ctx.perform('indent', props.node),
    },
    {
        id: 'outdent',
        label: 'Move out one level',
        icon: IndentDecrease,
        hidden: ctx.limits.maxDepth < 2,
        disabledReason: outdentReason(ctx.roots, props.node.key, ctx.limits),
        onSelect: () => ctx.perform('outdent', props.node),
    },
    {
        id: 'remove',
        label: 'Remove item',
        icon: Trash2,
        destructive: true,
        onSelect: () => ctx.perform('remove', props.node),
    },
]);

// CRUD-06: Space lifts the item, ↑ ↓ move it, → / Tab nest it, ← / Shift+Tab un-nest it, Space or
// Escape drops it. Tab only restructures while an item is lifted, so tabbing through the list
// never changes it.
function onHandleKey(event: KeyboardEvent): void {
    const node = props.node;

    if (event.key === ' ' || event.key === 'Enter') {
        event.preventDefault();
        ctx.lifted = lifted.value ? null : node.key;
        ctx.announce(
            lifted.value
                ? `${label.value} lifted. Use the arrow keys to move it, Space to drop.`
                : `${label.value} dropped.`,
        );

        return;
    }

    if (!lifted.value) {
        return;
    }

    const actionFor: Record<
        string,
        'moveUp' | 'moveDown' | 'indent' | 'outdent'
    > = {
        ArrowUp: 'moveUp',
        ArrowDown: 'moveDown',
        ArrowRight: 'indent',
        ArrowLeft: 'outdent',
    };
    const action =
        event.key === 'Tab'
            ? event.shiftKey
                ? 'outdent'
                : 'indent'
            : actionFor[event.key];

    if (event.key === 'Escape') {
        event.preventDefault();
        ctx.lifted = null;
        ctx.announce(`${label.value} dropped.`);

        return;
    }

    if (action) {
        event.preventDefault();
        ctx.perform(action, node);
    }
}
</script>

<template>
    <div
        :id="rowDomId(path)"
        :class="
            cn(
                'group flex items-start gap-1 border-b py-2.5 pr-1 sm:gap-2',
                lifted && 'rounded-md bg-accent ring-2 ring-ring',
            )
        "
    >
        <button
            v-if="!ctx.readonly"
            type="button"
            data-drag-handle
            :data-handle-key="node.key"
            :class="
                cn(
                    'mt-0.5 flex size-9 shrink-0 cursor-grab items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground active:cursor-grabbing',
                    lifted && 'text-foreground',
                )
            "
            :aria-label="`Move ${label}. Press Space to lift it, then the arrow keys.`"
            :aria-pressed="lifted"
            @keydown="onHandleKey"
            @blur="lifted && (ctx.lifted = null)"
        >
            <GripVertical class="size-4" aria-hidden="true" />
        </button>

        <button
            v-if="node.children.length > 0"
            type="button"
            class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-foreground"
            :aria-expanded="!collapsed"
            :aria-label="`${collapsed ? 'Show' : 'Hide'} the ${node.children.length} sub-items of ${label}`"
            @click="ctx.toggleCollapsed(node.key)"
        >
            <ChevronRight v-if="collapsed" class="size-4" aria-hidden="true" />
            <ChevronDown v-else class="size-4" aria-hidden="true" />
        </button>

        <component
            :is="ctx.readonly ? 'div' : 'button'"
            :type="ctx.readonly ? undefined : 'button'"
            class="min-h-11 min-w-0 flex-1 rounded-md px-1 py-1 text-left"
            :class="!ctx.readonly && 'hover:bg-accent/60'"
            @click="!ctx.readonly && ctx.edit(node)"
        >
            <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <span class="truncate text-sm font-medium">{{ label }}</span>
                <span
                    v-if="node.is_mega"
                    class="text-overline tracking-[0.16em] text-muted-foreground uppercase"
                >
                    Mega menu · {{ node.mega_columns.length }}
                    {{ node.mega_columns.length === 1 ? 'column' : 'columns' }}
                </span>
                <span
                    v-if="flag"
                    class="text-overline tracking-[0.16em] text-warning uppercase"
                >
                    Hidden on the site
                </span>
            </span>
            <span class="mt-0.5 block truncate text-xs text-muted-foreground">
                {{ meta }}
            </span>
            <span v-if="flag" class="mt-0.5 block text-xs text-warning">
                {{ flag.replace('Hidden on the site: ', 'Why: ') }}
            </span>
            <span
                v-for="message in messages.slice(0, 2)"
                :key="message"
                class="mt-0.5 block text-xs text-destructive"
            >
                {{ message }}
            </span>
        </component>

        <ErrorBadge :count="errorCount" class="mt-2.5" />

        <DataTableRowActions
            v-if="!ctx.readonly"
            :actions="actions"
            :row-label="label"
            class="mt-0.5"
        />
    </div>
</template>
