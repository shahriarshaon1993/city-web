<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { RotateCcw, Trash2, UserRound } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import RestoreConflictDialog from '@/components/admin/recycle-bin/RestoreConflictDialog.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime } from '@/lib/admin-format';
import {
    destroy,
    destroyMany,
    restore,
    restoreMany,
} from '@/routes/admin/recycle-bin';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    RecycleBinRow,
    RequestData,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// DEL-04: one DataTable across every bin type. Restore brings an item back as it was (conflicts
// are decided in a dialog, DEL-05); Delete forever asks to type the name (DEL-07).
const props = defineProps<{
    rows: RecycleBinRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canShareViews: boolean;
}>();

const confirm = useConfirm();

const columns: DataTableColumn[] = [
    { id: 'label', label: 'Item', required: true, sortable: true, width: 320 },
    { id: 'type', label: 'Type', sortable: true, width: 140 },
    { id: 'deleted_by', label: 'Deleted by', width: 200 },
    { id: 'deleted_at', label: 'Deleted', sortable: true, width: 200 },
    { id: 'purge_at', label: 'Auto-delete', sortable: true, width: 160 },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'type',
        label: 'Type',
        kind: 'enum',
        options: props.filterOptions.type,
    },
    {
        id: 'deleted_by',
        label: 'Deleted by',
        kind: 'enum',
        options: props.filterOptions.deleted_by,
    },
    { id: 'deleted', label: 'Deleted on', kind: 'date' },
]);

const plural = (count: number): string => (count === 1 ? 'item' : 'items');

// --- restore ----------------------------------------------------------------------------------------
const conflictRow = ref<RecycleBinRow | null>(null);
const conflictOpen = ref(false);
const restoring = ref(false);

function restoreOne(row: RecycleBinRow): void {
    if (row.conflicts.length > 0) {
        conflictRow.value = row;
        conflictOpen.value = true;

        return;
    }

    router.post(restore.url(row.id), {}, { preserveScroll: true });
}

function restoreWith(resolution: Record<string, string | boolean>): void {
    if (!conflictRow.value) {
        return;
    }

    restoring.value = true;
    router.post(
        restore.url(conflictRow.value.id),
        { resolution },
        {
            preserveScroll: true,
            onSuccess: () => {
                conflictOpen.value = false;
            },
            onFinish: () => {
                restoring.value = false;
            },
        },
    );
}

function selectionPayload(selection: TableSelection): RequestData {
    return selection.all
        ? { all: true, ...selection.query }
        : { ids: selection.ids };
}

function restoreSelection(selection: TableSelection): void {
    router.post(restoreMany.url(), selectionPayload(selection), {
        preserveScroll: true,
    });
}

// --- delete forever ---------------------------------------------------------------------------------
async function forgetOne(row: RecycleBinRow): Promise<void> {
    const answer = await confirm({
        title: `Delete ${row.type_label.toLowerCase()} forever?`,
        subject: row.label,
        description:
            'This removes it and its private files for good. It cannot be undone and it will not come back from anywhere.',
        confirmLabel: `Delete ${row.type_label.toLowerCase()} forever`,
        destructive: true,
        typeToConfirm: row.label,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

async function forgetSelection(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${plural(selection.count)} forever?`,
        description:
            'They are removed for good, with their private files. It cannot be undone. Items you may not delete forever are skipped.',
        confirmLabel: `Delete ${plural(selection.count)} forever`,
        destructive: true,
        typeToConfirm: `DELETE ${selection.count}`,
    });

    if (answer) {
        router.delete(destroyMany.url(), {
            data: selectionPayload(selection),
            preserveScroll: true,
        });
    }
}

// --- table wiring -----------------------------------------------------------------------------------
function rowActions(row: RecycleBinRow): DataTableRowAction[] {
    return [
        {
            id: 'restore',
            label: 'Restore',
            icon: RotateCcw,
            hidden: !row.can.restore,
            onSelect: () => restoreOne(row),
        },
        {
            id: 'forever',
            label: 'Delete forever',
            icon: Trash2,
            destructive: true,
            hidden: !row.can.forever,
            onSelect: () => void forgetOne(row),
        },
    ];
}

const bulkActions: DataTableBulkAction[] = [
    { id: 'restore', label: 'Restore', icon: RotateCcw, run: restoreSelection },
    {
        id: 'forever',
        label: 'Delete forever',
        icon: Trash2,
        destructive: true,
        run: (selection) => void forgetSelection(selection),
    },
];

/** DEL-06: "in 12 days", "tomorrow", "today", or "Never" for users. */
function daysLeft(iso: string): string {
    const days = Math.ceil((new Date(iso).getTime() - Date.now()) / 86_400_000);

    if (days <= 0) {
        return 'Today';
    }

    return days === 1 ? 'Tomorrow' : `In ${days} days`;
}
</script>

<template>
    <Head title="Recycle bin" />

    <AdminPageHeader
        title="Recycle bin"
        description="Deleted items wait here. Restore one to bring it back, or delete it forever. Items without an auto-delete date stay until you remove them."
    />

    <DataTable
        table-key="recycle-bin"
        noun="item"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search deleted items"
        :row-label="(row: RecycleBinRow) => row.label"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        selectable
        empty-title="The Recycle bin is empty"
        empty-description="Items you delete from a list appear here, so a mistake is never final."
    >
        <template #cell-label="{ row, search }">
            <div class="flex min-w-0 items-center gap-3">
                <img
                    v-if="row.thumbnail"
                    :src="row.thumbnail"
                    alt=""
                    class="size-9 shrink-0 rounded-md object-cover"
                />
                <span
                    v-else
                    class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                    aria-hidden="true"
                >
                    <UserRound class="size-4" />
                </span>
                <HighlightText
                    class="truncate font-medium"
                    :text="row.label"
                    :term="search"
                />
            </div>
        </template>
        <template #cell-type="{ row }">{{ row.type_label }}</template>
        <template #cell-deleted_by="{ row }">
            <span v-if="row.deleted_by">{{ row.deleted_by }}</span>
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-deleted_at="{ row }">
            <span class="tabular-nums">{{
                formatDateTime(row.deleted_at).split(' · ')[0]
            }}</span>
        </template>
        <template #cell-purge_at="{ row }">
            <template v-if="row.purge_at">
                <span class="tabular-nums">{{
                    formatDateTime(row.purge_at).split(' · ')[0]
                }}</span>
                <span class="ml-2 text-xs text-muted-foreground">{{
                    daysLeft(row.purge_at)
                }}</span>
            </template>
            <span v-else class="text-muted-foreground">Never</span>
        </template>
    </DataTable>

    <RestoreConflictDialog
        v-if="conflictRow"
        v-model:open="conflictOpen"
        :label="conflictRow.label"
        :conflicts="conflictRow.conflicts"
        :processing="restoring"
        @submit="restoreWith"
    />
</template>
