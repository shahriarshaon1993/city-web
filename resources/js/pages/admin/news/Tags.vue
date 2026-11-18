<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ExternalLink, GitMerge, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import NewsTabs from '@/components/admin/news/NewsTabs.vue';
import TagMergeDialog from '@/components/admin/news/TagMergeDialog.vue';
import TagRenameDialog from '@/components/admin/news/TagRenameDialog.vue';
import { useConfirm } from '@/composables/useConfirm';
import { destroy } from '@/routes/admin/news/tags';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterOption,
    TableMeta,
    TableViewItem,
    TagRow,
} from '@/types';

// ADM-SCR-08 News › Tags (FR-NEWS-003): tags are created while writing a post, so this screen only
// renames, merges and deletes. A tag has no Recycle bin: deleting it removes it from its posts for good,
// and the dialog says how many.
defineProps<{
    rows: TagRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    all: Array<{ id: number; name: string }>;
}>();

const columns: DataTableColumn[] = [
    { id: 'name', label: 'Tag', required: true, sortable: true, width: 280 },
    { id: 'slug', label: 'URL', width: 260 },
    {
        id: 'posts_count',
        label: 'Posts',
        sortable: true,
        width: 120,
        align: 'right',
    },
];

const renameOpen = ref(false);
const mergeOpen = ref(false);
const current = ref<TagRow | null>(null);

function rename(row: TagRow): void {
    current.value = row;
    renameOpen.value = true;
}

function mergeInto(row: TagRow): void {
    current.value = row;
    mergeOpen.value = true;
}

const confirm = useConfirm();

async function deleteOne(row: TagRow): Promise<void> {
    const answer = await confirm({
        title: 'Delete this tag?',
        subject: row.name,
        description:
            row.posts_count > 0
                ? `It is removed from ${row.posts_count} ${row.posts_count === 1 ? 'post' : 'posts'} and its page leaves the website. A tag is deleted for good: there is no Recycle bin and no undo.`
                : 'A tag is deleted for good: there is no Recycle bin and no undo.',
        confirmLabel: 'Delete tag',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

function rowActions(row: TagRow): DataTableRowAction[] {
    return [
        {
            id: 'rename',
            label: 'Rename',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => rename(row),
        },
        {
            id: 'merge',
            label: 'Merge other tags into this one…',
            icon: GitMerge,
            hidden: !row.can.edit,
            onSelect: () => mergeInto(row),
        },
        {
            id: 'view',
            label: 'View on site',
            icon: ExternalLink,
            hidden: row.posts_count === 0,
            onSelect: () => window.open(row.path, '_blank', 'noopener'),
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !row.can.delete,
            onSelect: () => void deleteOne(row),
        },
    ];
}
</script>

<template>
    <Head title="News tags" />

    <ListPageHeader
        title="News"
        description="Tags are added while writing a post. Names that match, in any letter case, are one tag. Rename, merge or delete them here."
    >
        <template #tabs>
            <NewsTabs current="tags" />
        </template>
    </ListPageHeader>

    <DataTable
        table-key="tags"
        noun="tag"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search tags"
        :row-label="(row: TagRow) => row.name"
        :row-actions="rowActions"
        :clickable-rows="canManage"
        empty-title="No tags yet"
        empty-description="Tags appear here once you add them to a post."
        @row-click="(row: TagRow) => row.can.edit && rename(row)"
    >
        <template #cell-name="{ row, search }">
            <span class="font-medium"
                ><HighlightText :text="row.name" :term="search"
            /></span>
        </template>
        <template #cell-slug="{ row }">
            <span class="font-mono text-xs text-muted-foreground">{{
                row.path
            }}</span>
        </template>
        <template #cell-posts_count="{ row }">
            <span class="tabular-nums">{{ row.posts_count }}</span>
        </template>
    </DataTable>

    <TagRenameDialog v-model:open="renameOpen" :tag="current" />
    <TagMergeDialog v-model:open="mergeOpen" :tag="current" :all="all" />
</template>
