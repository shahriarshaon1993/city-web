<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { FolderInput, FolderPlus, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, nextTick, provide, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { destroy, rename, store } from '@/routes/admin/media/folders';
import type {
    DataTableRowAction,
    MediaFolderNode,
    MediaFolderTree,
} from '@/types';
import MediaFolderInput from './MediaFolderInput.vue';
import MediaFolderMoveDialog from './MediaFolderMoveDialog.vue';
import MediaFolderRow from './MediaFolderRow.vue';
import { FOLDER_TREE } from './folder-tree';

// CMP-ADM-MEDIA folders card: All files, Unfiled, the nested folders (≤ 3 levels, CMS-MED-002)
// with counts, and "+ New folder". Folders are edited inline (CRUD matrix): new subfolder, rename,
// move, delete (only when empty, for good — DEL-09). In the library each entry is a link that sets
// `filter[folder]`; in the picker (`hrefFor` absent) it emits `select`.
const props = withDefaults(
    defineProps<{
        tree: MediaFolderTree;
        /** '' = All files, 'unfiled', or a folder id. */
        current: string;
        hrefFor?: (key: string) => string;
        canManage?: boolean;
    }>(),
    { hrefFor: undefined, canManage: false },
);

const emit = defineEmits<{ select: [key: string] }>();

const confirm = useConfirm();

// --- inline create / rename -------------------------------------------------------------------
const creatingUnder = ref<number | 'root' | null>(null);
const renaming = ref<number | null>(null);
const createForm = useForm({ name: '', parent_id: null as number | null });
const renameForm = useForm({ name: '' });

function startCreate(parent: MediaFolderNode | null): void {
    renaming.value = null;
    createForm.reset();
    createForm.clearErrors();
    createForm.parent_id = parent?.id ?? null;
    creatingUnder.value = parent?.id ?? 'root';
}

function submitCreate(): void {
    if (createForm.name.trim() === '') {
        creatingUnder.value = null;

        return;
    }

    createForm.post(store.url(), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            creatingUnder.value = null;
            createForm.reset();
        },
    });
}

function startRename(folder: MediaFolderNode): void {
    creatingUnder.value = null;
    renameForm.clearErrors();
    renameForm.name = folder.name;
    renaming.value = folder.id;
}

function submitRename(folder: MediaFolderNode): void {
    if (
        renameForm.name.trim() === '' ||
        renameForm.name.trim() === folder.name
    ) {
        renaming.value = null;

        return;
    }

    renameForm.patch(rename.url(folder.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            renaming.value = null;
        },
    });
}

// --- move / delete ----------------------------------------------------------------------------
const moving = ref<MediaFolderNode | null>(null);
const moveOpen = ref(false);

async function remove(folder: MediaFolderNode): Promise<void> {
    const answer = await confirm({
        title: 'Delete this folder?',
        subject: folder.path,
        description:
            'The empty folder is deleted for good; it does not go to the Recycle bin. Files are never inside a deleted folder — only empty folders can be deleted.',
        confirmLabel: 'Delete folder',
        destructive: true,
    });

    if (!answer) {
        return;
    }

    router.delete(destroy.url(folder.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (props.current === String(folder.id)) {
                select('');
            }
        },
    });
}

function actionsFor(folder: MediaFolderNode): DataTableRowAction[] {
    return [
        {
            id: 'new',
            label: 'New subfolder',
            icon: FolderPlus,
            disabledReason:
                folder.depth >= 3 ? 'Folders go 3 levels deep at most.' : null,
            onSelect: () => startCreate(folder),
        },
        {
            id: 'rename',
            label: 'Rename',
            icon: Pencil,
            onSelect: () => startRename(folder),
        },
        {
            id: 'move',
            label: 'Move to…',
            icon: FolderInput,
            onSelect: () => {
                moving.value = folder;
                moveOpen.value = true;
            },
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            disabledReason: folder.delete_block,
            onSelect: () => void remove(folder),
        },
    ];
}

function select(key: string): void {
    if (props.hrefFor) {
        router.visit(props.hrefFor(key), {
            preserveScroll: true,
            preserveState: true,
        });
    } else {
        emit('select', key);
    }
}

// Shared with the recursive rows.
provide(FOLDER_TREE, {
    current: computed(() => props.current),
    hrefFor: props.hrefFor,
    canManage: computed(() => props.canManage),
    creatingUnder,
    renaming,
    createForm,
    renameForm,
    actionsFor,
    select,
    submitCreate,
    submitRename,
    cancelEdit: () => {
        creatingUnder.value = null;
        renaming.value = null;
    },
});

const rootInput = ref<InstanceType<typeof MediaFolderInput> | null>(null);

async function newRootFolder(): Promise<void> {
    startCreate(null);
    await nextTick();
    rootInput.value?.focus();
}
</script>

<template>
    <nav aria-label="Folders" class="grid gap-1 text-sm">
        <ul class="grid gap-0.5">
            <MediaFolderRow
                :entry="{ key: '', label: 'All files', count: tree.all }"
            />
            <MediaFolderRow
                :entry="{
                    key: 'unfiled',
                    label: 'Unfiled',
                    count: tree.unfiled,
                }"
            />
        </ul>

        <ul
            v-if="tree.folders.length || creatingUnder === 'root'"
            class="mt-2 grid gap-0.5 border-t border-foreground/10 pt-2"
        >
            <MediaFolderRow
                v-for="folder in tree.folders"
                :key="folder.id"
                :folder="folder"
            />
            <li v-if="creatingUnder === 'root'" class="px-1 py-1">
                <MediaFolderInput
                    ref="rootInput"
                    v-model="createForm.name"
                    label="New folder name"
                    :error="
                        createForm.errors.name ?? createForm.errors.parent_id
                    "
                    :busy="createForm.processing"
                    @submit="submitCreate"
                    @cancel="creatingUnder = null"
                />
            </li>
        </ul>

        <Button
            v-if="canManage && creatingUnder !== 'root'"
            type="button"
            variant="ghost"
            size="sm"
            class="mt-2 justify-start rounded-lg text-muted-foreground hover:text-foreground"
            @click="newRootFolder"
        >
            <Plus aria-hidden="true" />
            New folder
        </Button>

        <MediaFolderMoveDialog
            v-if="moving"
            v-model:open="moveOpen"
            :folder="moving"
            :tree="tree"
        />
    </nav>
</template>
