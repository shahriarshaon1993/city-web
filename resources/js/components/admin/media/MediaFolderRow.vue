<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Folder, FolderOpen, Images, Inbox } from '@lucide/vue';
import { computed, inject } from 'vue';
import DataTableRowActions from '@/components/admin/data-table/DataTableRowActions.vue';
import { cn } from '@/lib/utils';
import type { MediaFolderNode } from '@/types';
import MediaFolderInput from './MediaFolderInput.vue';
import { FOLDER_TREE } from './folder-tree';

defineOptions({ name: 'MediaFolderRow' });

// One entry of the folder tree: the two fixed ones (`entry`) or a folder with its subfolders.
const props = defineProps<{
    folder?: MediaFolderNode;
    entry?: { key: string; label: string; count: number };
}>();

const tree = inject(FOLDER_TREE)!;

const key = computed(() =>
    props.folder ? String(props.folder.id) : (props.entry?.key ?? ''),
);
const label = computed(() => props.folder?.name ?? props.entry?.label ?? '');
const count = computed(() => props.folder?.count ?? props.entry?.count ?? 0);
const active = computed(() => tree.current.value === key.value);
const icon = computed(() =>
    props.entry
        ? key.value === ''
            ? Images
            : Inbox
        : active.value
          ? FolderOpen
          : Folder,
);
const indent = computed(() => ({
    paddingLeft: `${((props.folder?.depth ?? 1) - 1) * 16 + 8}px`,
}));

const itemClass = computed(() =>
    cn(
        'flex min-h-9 min-w-0 flex-1 items-center gap-2.5 rounded-lg pr-2.5 text-left transition-colors',
        active.value
            ? 'bg-card font-medium text-foreground shadow-xs ring-1 ring-foreground/10'
            : 'text-muted-foreground hover:bg-foreground/5 hover:text-foreground',
    ),
);
</script>

<template>
    <li>
        <div
            v-if="folder && tree.renaming.value === folder.id"
            class="px-1 py-1"
            :style="indent"
        >
            <MediaFolderInput
                v-model="tree.renameForm.name"
                :label="`New name for ${folder.name}`"
                :error="tree.renameForm.errors.name"
                :busy="tree.renameForm.processing"
                @submit="tree.submitRename(folder)"
                @cancel="tree.cancelEdit()"
            />
        </div>

        <div v-else class="group/folder flex items-center gap-1">
            <component
                :is="tree.hrefFor ? Link : 'button'"
                v-bind="
                    tree.hrefFor
                        ? {
                              href: tree.hrefFor(key),
                              preserveScroll: true,
                              preserveState: true,
                          }
                        : { type: 'button' }
                "
                :class="itemClass"
                :style="indent"
                :aria-current="active ? 'true' : undefined"
                @click="!tree.hrefFor && tree.select(key)"
            >
                <component
                    :is="icon"
                    :class="cn('size-4 shrink-0', active && 'text-primary')"
                    aria-hidden="true"
                />
                <span class="min-w-0 flex-1 truncate">{{ label }}</span>
                <span
                    :class="
                        cn(
                            'text-xs tabular-nums',
                            active ? 'text-foreground' : 'opacity-70',
                        )
                    "
                    >{{ count }}</span
                >
            </component>
            <DataTableRowActions
                v-if="folder && tree.canManage.value"
                :actions="tree.actionsFor(folder)"
                :row-label="folder.name"
                class="opacity-100 pointer-fine:opacity-0 pointer-fine:group-focus-within/folder:opacity-100 pointer-fine:group-hover/folder:opacity-100"
            />
        </div>

        <ul
            v-if="
                folder &&
                (folder.children.length ||
                    tree.creatingUnder.value === folder.id)
            "
            class="grid gap-0.5"
        >
            <MediaFolderRow
                v-for="child in folder.children"
                :key="child.id"
                :folder="child"
            />
            <li
                v-if="tree.creatingUnder.value === folder.id"
                class="py-1 pr-1"
                :style="{ paddingLeft: `${folder.depth * 16 + 8}px` }"
            >
                <MediaFolderInput
                    v-model="tree.createForm.name"
                    :label="`New folder inside ${folder.name}`"
                    :error="
                        tree.createForm.errors.name ??
                        tree.createForm.errors.parent_id
                    "
                    :busy="tree.createForm.processing"
                    @submit="tree.submitCreate()"
                    @cancel="tree.cancelEdit()"
                />
            </li>
        </ul>
    </li>
</template>
