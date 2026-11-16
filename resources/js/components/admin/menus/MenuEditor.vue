<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Plus, Undo2 } from '@lucide/vue';
import { computed, nextTick, provide, reactive, ref } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import CharCounter from '@/components/admin/CharCounter.vue';
import FieldHelp from '@/components/admin/FieldHelp.vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    blankNode,
    cloneTree,
    countNodes,
    hiddenCount,
    hydrate,
    indent,
    locate,
    moveDown,
    moveUp,
    outdent,
    remove,
    rowDomId,
    rowPathOfError,
    toPayload,
} from '@/lib/menu-tree';
import { notify } from '@/lib/toast';
import { cn } from '@/lib/utils';
import { update } from '@/routes/admin/menus';
import type { MenuInfo, MenuNode, MenuOptions, MenuTab } from '@/types';
import {
    menuTreeContextKey,
    type MenuTreeAction,
    type MenuTreeContext,
} from './context';
import MenuItemDialog from './MenuItemDialog.vue';
import MenuTreeList from './MenuTreeList.vue';

// CMS-NAV-005 / CRUD-04: one tab = one menu = one editor. The whole tree is edited in the
// browser (drag, keyboard, dialog), can be undone until it is saved, and is sent in one request;
// the server answers with the saved tree, so the page keys this component by save time.
const props = defineProps<{
    tabs: MenuTab[];
    menu: MenuInfo;
    options: MenuOptions;
    canManage: boolean;
    lastSaved: string | null;
}>();

// `items` is typed loosely on purpose: Inertia's form type recurses forever on a tree of nodes.
// Every function that touches it takes a `MenuNode[]`.
const form = useForm<{ name: string; items: any }>({
    name: props.menu.name,
    items: hydrate(props.menu.items),
});

const readonly = !props.canManage;
const limits = {
    maxDepth: props.menu.max_depth,
    maxItems: props.menu.max_items,
    maxChildren: props.menu.max_children,
};

// --- Undo until saved -----------------------------------------------------------------------------
const history = ref<MenuNode[][]>([]);
const HISTORY_LIMIT = 30;

function snapshot(): void {
    history.value.push(cloneTree(form.items));

    if (history.value.length > HISTORY_LIMIT) {
        history.value.shift();
    }
}

function undo(): void {
    const previous = history.value.pop();

    if (previous) {
        form.items = previous;
        context.lifted = null;
        announce('Last change undone.');
    }
}

// --- Announcements (live region) --------------------------------------------------------------------
const announcement = ref('');

function announce(message: string): void {
    announcement.value = '';
    void nextTick(() => {
        announcement.value = message;
    });
}

// --- Dialog ---------------------------------------------------------------------------------------
const dialogOpen = ref(false);
const editing = ref<MenuNode | null>(null);
const draft = ref<MenuNode | null>(null);
/** Where a new item goes: null = top level, else the parent. */
const addTo = ref<MenuNode | null>(null);

function openEdit(node: MenuNode): void {
    editing.value = node;
    draft.value = null;
    dialogOpen.value = true;
}

function openAdd(parent: MenuNode | null): void {
    editing.value = null;
    addTo.value = parent;
    draft.value = blankNode();
    dialogOpen.value = true;
}

const dialogNode = computed(() => editing.value);
const editingSpot = computed(() =>
    editing.value ? locate(form.items, editing.value.key) : null,
);
const dialogTopLevel = computed(() =>
    editing.value
        ? (editingSpot.value?.depth ?? 1) === 1
        : addTo.value === null,
);
const canMega = computed(
    () => props.menu.supports_mega && dialogTopLevel.value,
);
const megaReason = computed(() =>
    editing.value && editing.value.children.length > 0
        ? 'This item has sub-items. A mega menu replaces them, so remove the sub-items first.'
        : null,
);

function applyDialog(copy: MenuNode): void {
    snapshot();

    if (editing.value) {
        const target = editing.value;

        Object.assign(target, {
            label: copy.label.trim(),
            link_type: copy.link_type,
            linkable_id: copy.linkable_id,
            url: copy.url,
            anchor: copy.anchor,
            new_tab: copy.new_tab,
            link: copy.link,
            icon: copy.icon,
            description: copy.description,
            is_mega: copy.is_mega,
            mega_columns: copy.mega_columns,
        });
        announce(`${target.label} updated.`);

        return;
    }

    const list = addTo.value ? addTo.value.children : form.items;

    list.push(copy);
    announce(`${copy.label} added.`);
}

// --- Tree actions -----------------------------------------------------------------------------------
function focusHandle(key: string): void {
    void nextTick(() => {
        document
            .querySelector<HTMLElement>(`[data-handle-key="${key}"]`)
            ?.focus();
    });
}

function perform(action: MenuTreeAction, node: MenuNode): void {
    const label = node.label || 'The item';

    if (action === 'remove') {
        snapshot();
        remove(form.items, node.key);
        announce(`${label} removed.`);
        notify.info(`“${label}” removed`, {
            description: 'Save to apply, or undo.',
            duration: 8000,
            action: { label: 'Undo', onClick: undo },
        });

        return;
    }

    snapshot();

    const done = {
        moveUp: () => moveUp(form.items, node.key),
        moveDown: () => moveDown(form.items, node.key),
        indent: () => indent(form.items, node.key, limits),
        outdent: () => outdent(form.items, node.key, limits),
    }[action]();

    if (!done) {
        history.value.pop();

        return;
    }

    const spot = locate(form.items, node.key);

    announce(
        spot
            ? `${label} is now item ${spot.index + 1} of ${spot.list.length}${spot.depth > 1 ? `, under ${spot.parent?.label ?? 'its parent'}` : ''}.`
            : `${label} moved.`,
    );

    if (context.lifted === node.key) {
        focusHandle(node.key);
    }
}

let beforeDrag: string | null = null;

const context: MenuTreeContext = reactive({
    readonly,
    limits,
    get roots() {
        return form.items;
    },
    get errors() {
        return form.errors as Record<string, string | undefined>;
    },
    dragging: false,
    lifted: null as string | null,
    collapsed: [] as string[],
    edit: openEdit,
    addChild: (node: MenuNode) => openAdd(node),
    perform,
    dragStart: () => {
        context.dragging = true;
        beforeDrag = JSON.stringify(toPayload(form.items));
        history.value.push(cloneTree(form.items));
    },
    dragEnd: () => {
        context.dragging = false;

        // A drop that changed nothing leaves no undo step.
        if (beforeDrag === JSON.stringify(toPayload(form.items))) {
            history.value.pop();
        } else {
            announce('Item moved.');
        }
    },
    toggleCollapsed: (key: string) => {
        context.collapsed = context.collapsed.includes(key)
            ? context.collapsed.filter((item) => item !== key)
            : [...context.collapsed, key];
    },
    focusHandle,
    announce,
});

provide(menuTreeContextKey, context);

// --- Save -------------------------------------------------------------------------------------------
const total = computed(() => countNodes(form.items));
const hidden = computed(() => hiddenCount(form.items));
const atLimit = computed(() => form.items.length >= limits.maxItems);

function save(): void {
    form.transform((data) => ({
        name: data.name,
        items: toPayload(data.items),
    })).put(update.url({ menu: props.menu.key }), {
        preserveScroll: true,
    });
}

function fieldId(key: string): string {
    if (key === 'name') {
        return 'menu-name';
    }

    const row = rowPathOfError(key);

    return row ? rowDomId(row) : 'menu-items';
}

const saveReason = computed(() =>
    readonly ? 'You can view menus but not change them.' : null,
);

const addReason = computed(() =>
    atLimit.value
        ? `This menu holds ${limits.maxItems} main items at most.`
        : null,
);
</script>

<template>
    <AdminEditor
        :title="`${menu.name} menu`"
        :description="menu.appears_in"
        :form="form"
        :last-saved="lastSaved"
        :field-id="fieldId"
        :save-reason="saveReason"
        :save-label="'Save menu'"
        @save="save"
    >
        <template #tabs>
            <nav aria-label="Menus" class="-mb-px flex gap-5 overflow-x-auto">
                <Link
                    v-for="tab in tabs"
                    :key="tab.key"
                    :href="tab.href"
                    :aria-current="tab.key === menu.key ? 'page' : undefined"
                    :class="
                        cn(
                            'inline-flex h-11 shrink-0 items-center gap-2 border-b-2 px-1 text-sm font-medium whitespace-nowrap transition-colors',
                            tab.key === menu.key
                                ? 'border-primary text-foreground'
                                : 'border-transparent text-muted-foreground hover:text-foreground',
                        )
                    "
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.hidden > 0"
                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-warning/15 px-1.5 text-xs text-warning"
                        :title="`${tab.hidden} hidden on the site`"
                    >
                        {{ tab.hidden }}
                        <span class="sr-only">links hidden on the site</span>
                    </span>
                </Link>
            </nav>
        </template>

        <div class="flex max-w-[1100px] flex-col gap-6">
            <AdminSection
                v-if="menu.is_footer_column"
                title="Column heading"
                description="The title above this column of links in the footer."
            >
                <div class="grid max-w-md gap-2">
                    <Label for="menu-name">Heading</Label>
                    <Input
                        id="menu-name"
                        v-model="form.name"
                        maxlength="40"
                        autocomplete="off"
                        :readonly="readonly"
                        aria-describedby="menu-name-help"
                        :aria-invalid="!!form.errors.name || undefined"
                    />
                    <div class="flex items-start justify-between gap-3">
                        <FieldHelp id="menu-name">
                            Up to 40 characters.
                        </FieldHelp>
                        <CharCounter :value="form.name" :max="40" />
                    </div>
                    <InputError :message="form.errors.name" />
                </div>
            </AdminSection>

            <AdminSection
                title="Items"
                :description="
                    menu.max_depth > 1
                        ? `Up to ${menu.max_items} main items, each with up to ${menu.max_children} sub-items.`
                        : `Up to ${menu.max_items} links, one level.`
                "
            >
                <div id="menu-items" tabindex="-1" class="grid gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <ReasonButton
                            v-if="!readonly"
                            type="button"
                            :reason="addReason"
                            @click="openAdd(null)"
                        >
                            <Plus aria-hidden="true" />
                            Add item
                        </ReasonButton>
                        <ReasonButton
                            v-if="!readonly"
                            type="button"
                            variant="outline"
                            :reason="
                                history.length === 0
                                    ? 'Nothing to undo yet.'
                                    : null
                            "
                            @click="undo"
                        >
                            <Undo2 aria-hidden="true" />
                            Undo
                        </ReasonButton>
                        <p class="ml-auto text-xs text-muted-foreground">
                            {{ total }} {{ total === 1 ? 'link' : 'links' }}
                        </p>
                    </div>

                    <p
                        v-if="hidden > 0"
                        class="rounded-md border border-warning/40 bg-warning/5 px-3 py-2 text-sm"
                        role="status"
                    >
                        {{ hidden }}
                        {{ hidden === 1 ? 'link is' : 'links are' }} hidden on
                        the website because
                        {{
                            hidden === 1 ? 'its target is' : 'their targets are'
                        }}
                        not published or
                        {{ hidden === 1 ? 'has' : 'have' }} been removed.
                        {{ hidden === 1 ? 'It is' : 'They are' }} marked below.
                    </p>

                    <MenuTreeList
                        v-if="form.items.length > 0"
                        v-model="form.items"
                        :depth="1"
                        path="items"
                        :parent="null"
                    />

                    <p
                        v-else
                        class="rounded-lg border border-dashed px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        This menu has no items yet. It appears on the website
                        once you add one.
                        <template v-if="!readonly"
                            >Use “Add item” to start.</template
                        >
                    </p>

                    <p v-if="!readonly" class="text-xs text-muted-foreground">
                        Drag the grip to reorder, or use the keyboard: press
                        Space on the grip to lift an item, ↑ ↓ to move it, → or
                        Tab to nest it under the item above, ← or Shift+Tab to
                        move it out, Space to drop. The ⋯ menu offers the same
                        actions.
                    </p>
                </div>
            </AdminSection>
        </div>

        <p class="sr-only" role="status" aria-live="polite">
            {{ announcement }}
        </p>
    </AdminEditor>

    <MenuItemDialog
        v-model:open="dialogOpen"
        :node="dialogNode"
        :draft="draft"
        :options="options"
        :can-mega="canMega"
        :mega-reason="megaReason"
        :show-details="menu.supports_mega"
        @apply="applyDialog"
    />
</template>
