<script setup lang="ts">
import { computed, inject } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import { cn } from '@/lib/utils';
import type { MenuNode } from '@/types';
import { menuTreeContextKey, type MenuTreeContext } from './context';
import MenuTreeRow from './MenuTreeRow.vue';

// CMP-ADM-TREE: one level of the menu as a draggable list; an item's sub-items are the same
// component one level down. Dragging an item into another list is limited by the menu's rules
// (depth, counts, no sub-items under a mega item) through Sortable's `put` check.
const props = defineProps<{
    /** 1 = top level. */
    depth: number;
    /** Error-key path of this list: `items` or `items.2.children`. */
    path: string;
    parent: MenuNode | null;
}>();

const items = defineModel<MenuNode[]>({ required: true });

const injected = inject(menuTreeContextKey);

if (!injected) {
    throw new Error('MenuTreeList needs the menu tree context.');
}

const ctx: MenuTreeContext = injected;

const limit = computed(() =>
    props.depth === 1 ? ctx.limits.maxItems : ctx.limits.maxChildren,
);

// Sortable checks `put` only when an item arrives from another list.
const group = computed(() => ({
    name: 'menu',
    put: (_to: unknown, _from: unknown, element: HTMLElement): boolean => {
        if (items.value.length >= limit.value) {
            return false;
        }

        if (props.depth === 1) {
            return true;
        }

        return (
            !props.parent?.is_mega &&
            element.dataset.children === '0' &&
            element.dataset.mega === '0'
        );
    },
}));

const nestedAllowed = computed(() => props.depth < ctx.limits.maxDepth);
</script>

<template>
    <VueDraggable
        v-model="items"
        tag="ul"
        :group="group"
        :animation="150"
        handle="[data-drag-handle]"
        ghost-class="opacity-40"
        :disabled="ctx.readonly"
        :class="
            cn(
                'list-none',
                depth > 1 &&
                    'ml-5 min-h-1 border-l pl-4 sm:ml-7 [&:empty]:min-h-1',
                depth > 1 &&
                    ctx.dragging &&
                    'my-1 min-h-9 rounded-md border border-dashed bg-muted/40',
            )
        "
        :aria-label="depth === 1 ? 'Menu items' : 'Sub-items'"
        @start="ctx.dragStart"
        @end="ctx.dragEnd"
    >
        <li
            v-for="(node, index) in items"
            :key="node.key"
            :data-children="node.children.length"
            :data-mega="node.is_mega ? 1 : 0"
        >
            <MenuTreeRow
                :node="node"
                :path="`${path}.${index}`"
                :depth="depth"
                :first="index === 0"
                :last="index === items.length - 1"
            />
            <MenuTreeList
                v-if="nestedAllowed && !node.is_mega"
                v-show="!ctx.collapsed.includes(node.key)"
                v-model="node.children"
                :depth="depth + 1"
                :path="`${path}.${index}.children`"
                :parent="node"
            />
        </li>
    </VueDraggable>
</template>
