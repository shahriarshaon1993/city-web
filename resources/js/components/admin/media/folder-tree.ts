import type { InertiaForm } from '@inertiajs/vue3';
import type { ComputedRef, InjectionKey, Ref } from 'vue';
import type { DataTableRowAction, MediaFolderNode } from '@/types';

/** What MediaFolderTree shares with its recursive MediaFolderRow children. */
export type FolderTreeContext = {
    current: ComputedRef<string>;
    hrefFor?: (key: string) => string;
    canManage: ComputedRef<boolean>;
    creatingUnder: Ref<number | 'root' | null>;
    renaming: Ref<number | null>;
    createForm: InertiaForm<{ name: string; parent_id: number | null }>;
    renameForm: InertiaForm<{ name: string }>;
    actionsFor: (folder: MediaFolderNode) => DataTableRowAction[];
    select: (key: string) => void;
    submitCreate: () => void;
    submitRename: (folder: MediaFolderNode) => void;
    cancelEdit: () => void;
};

export const FOLDER_TREE: InjectionKey<FolderTreeContext> =
    Symbol('media-folder-tree');

/** Every folder in display order, flat, for selects ("Move to…", the drawer's folder field). */
export function flattenFolders(folders: MediaFolderNode[]): MediaFolderNode[] {
    return folders.flatMap((folder) => [
        folder,
        ...flattenFolders(folder.children),
    ]);
}
