<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { MailX, ShieldX, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime } from '@/lib/admin-format';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import { bulk, destroy, erase, unsubscribe } from '@/routes/admin/subscribers';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    SubscriberRow,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// ADM-SCR-12 Subscribers: pending (waiting for the confirmation link), active and unsubscribed
// addresses. Delete = Recycle bin with Undo; Erase removes the address for good (a person asked to
// be forgotten) and says so before it does.
const props = defineProps<{
    rows: SubscriberRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canShareViews: boolean;
    binCount: number;
}>();

const confirm = useConfirm();

const columns: DataTableColumn[] = [
    { id: 'email', label: 'Email', sortable: true, required: true, width: 300 },
    { id: 'status', label: 'Status', sortable: true, width: 150 },
    { id: 'source', label: 'Signed up on', width: 260 },
    { id: 'confirmed_at', label: 'Confirmed', sortable: true, width: 190 },
    { id: 'created_at', label: 'Added', sortable: true, width: 190 },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'status',
        label: 'Status',
        kind: 'enum',
        options: props.filterOptions.status,
    },
    { id: 'created', label: 'Added', kind: 'date' },
]);

const tone = {
    pending: 'warning',
    active: 'success',
    unsubscribed: 'neutral',
} as const;
const noun = (count: number): string =>
    count === 1 ? 'subscriber' : 'subscribers';

async function unsubscribeOne(row: SubscriberRow): Promise<void> {
    const answer = await confirm({
        title: 'Unsubscribe this address?',
        subject: row.email,
        description:
            'It stays in the list as unsubscribed, so it is never mailed again. The person can sign up again from the website.',
        confirmLabel: 'Unsubscribe',
    });

    if (answer) {
        router.post(unsubscribe.url(row.id), {}, { preserveScroll: true });
    }
}

async function deleteOne(row: SubscriberRow): Promise<void> {
    const answer = await confirm({
        title: 'Delete this subscriber?',
        subject: row.email,
        description:
            'The address moves to the Recycle bin and can be restored for 365 days. To remove it for good, use Erase.',
        confirmLabel: 'Delete subscriber',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

async function eraseOne(row: SubscriberRow): Promise<void> {
    const answer = await confirm({
        title: 'Erase this address for good?',
        subject: row.email,
        description:
            'Use this when the person asks to be forgotten. The address is removed completely. It does not go to the Recycle bin and cannot be restored.',
        confirmLabel: 'Erase address',
        destructive: true,
        typeToConfirm: row.email,
    });

    if (answer) {
        router.delete(erase.url(row.id), { preserveScroll: true });
    }
}

function postBulk(
    action: 'unsubscribe' | 'delete',
    selection: TableSelection,
): void {
    router.post(
        bulk.url(),
        {
            action,
            ...(selection.all
                ? { all: true, ...selection.query }
                : { ids: selection.ids }),
        },
        { preserveScroll: true },
    );
}

async function bulkDelete(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${noun(selection.count)}?`,
        description:
            'The addresses move to the Recycle bin and can be restored for 365 days.',
        confirmLabel: `Delete ${noun(selection.count)}`,
        destructive: true,
        typeToConfirm:
            selection.count > 20 ? `DELETE ${selection.count}` : undefined,
    });

    if (answer) {
        postBulk('delete', selection);
    }
}

async function bulkUnsubscribe(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Unsubscribe ${selection.count} ${noun(selection.count)}?`,
        description:
            'They stay in the list as unsubscribed and are never mailed again.',
        confirmLabel: 'Unsubscribe',
    });

    if (answer) {
        postBulk('unsubscribe', selection);
    }
}

function rowActions(row: SubscriberRow): DataTableRowAction[] {
    return [
        {
            id: 'unsubscribe',
            label: 'Unsubscribe',
            icon: MailX,
            hidden: !row.can.unsubscribe,
            onSelect: () => void unsubscribeOne(row),
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !row.can.delete,
            onSelect: () => void deleteOne(row),
        },
        {
            id: 'erase',
            label: 'Erase for good…',
            icon: ShieldX,
            destructive: true,
            hidden: !row.can.erase,
            onSelect: () => void eraseOne(row),
        },
    ];
}

const bulkActions: DataTableBulkAction[] = [
    {
        id: 'unsubscribe',
        label: 'Unsubscribe',
        icon: MailX,
        run: (selection) => void bulkUnsubscribe(selection),
    },
    {
        id: 'delete',
        label: 'Delete',
        icon: Trash2,
        destructive: true,
        run: (selection) => void bulkDelete(selection),
    },
];
</script>

<template>
    <Head title="Subscribers" />

    <ListPageHeader
        title="Subscribers"
        description="People who signed up for the newsletter. With email switched off in Settings, sign-ups are active at once; with it on, they wait for the confirmation link."
        :bin-count="binCount"
        :bin-href="
            binIndex.url({ query: { 'filter[type]': 'newsletter_subscriber' } })
        "
    />

    <DataTable
        table-key="subscribers"
        noun="subscriber"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search email address"
        :row-label="(row: SubscriberRow) => row.email"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        selectable
        empty-title="No subscribers yet"
        empty-description="Addresses appear here when visitors use the newsletter form in the footer or on the news page."
    >
        <template #cell-email="{ row, search }">
            <span class="font-medium">
                <HighlightText :text="row.email" :term="search" />
            </span>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge :label="row.status_label" :tone="tone[row.status]" />
        </template>
        <template #cell-source="{ row }">
            <span class="font-mono text-[13px] text-muted-foreground">{{
                row.source ?? '–'
            }}</span>
        </template>
        <template #cell-confirmed_at="{ row }">
            <span v-if="row.confirmed_at" class="tabular-nums">{{
                formatDateTime(row.confirmed_at)
            }}</span>
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-created_at="{ row }">
            <span v-if="row.created_at" class="tabular-nums">{{
                formatDateTime(row.created_at)
            }}</span>
        </template>
    </DataTable>
</template>
