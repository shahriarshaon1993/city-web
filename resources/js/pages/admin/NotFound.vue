<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    CornerUpRight,
    EyeOff,
    ExternalLink,
    ListFilter,
    Trash2,
    Undo2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import IgnoreListDialog from '@/components/admin/seo/IgnoreListDialog.vue';
import RedirectDialog from '@/components/admin/seo/RedirectDialog.vue';
import SeoTabs from '@/components/admin/seo/SeoTabs.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import {
    bulk,
    destroy,
    ignore,
    index as notFoundIndex,
} from '@/routes/admin/not-found';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    IgnoreList,
    NotFoundRow,
    RedirectDraft,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// ADM-SCR-14 404 monitor (SEO-TEC-009). "Create redirect" opens the redirect dialog with the
// address filled in; the row then shows "Redirected". Ignore is inline; Delete is for good (DEL-09).
const props = defineProps<{
    rows: NotFoundRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    ignoreList: IgnoreList;
    retentionDays: number;
}>();

const columns: DataTableColumn[] = [
    {
        id: 'path',
        label: 'Address',
        required: true,
        sortable: true,
        width: 320,
    },
    { id: 'hits', label: 'Hits', sortable: true, width: 110, align: 'right' },
    { id: 'status', label: 'Status', width: 150 },
    { id: 'referrer', label: 'Last referrer', width: 260 },
    { id: 'last_seen_at', label: 'Last seen', sortable: true, width: 190 },
    {
        id: 'first_seen_at',
        label: 'First seen',
        sortable: true,
        width: 140,
        hidden: true,
    },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'status',
        label: 'Status',
        kind: 'enum',
        options: props.filterOptions.status,
    },
    { id: 'hits', label: 'Hits', kind: 'number' },
    { id: 'last_seen', label: 'Last seen', kind: 'date' },
    { id: 'first_seen', label: 'First seen', kind: 'date' },
]);

// --- redirect dialog ------------------------------------------------------------------------------
const dialogOpen = ref(false);
const draft = ref<RedirectDraft | null>(null);
const ignoreOpen = ref(false);

function createRedirect(row: NotFoundRow): void {
    draft.value = { source: row.path, target: '', code: 301, is_active: true };
    dialogOpen.value = true;
}

// --- ignore / delete ----------------------------------------------------------------------------------
const confirm = useConfirm();
const noun = (count: number): string => (count === 1 ? 'address' : 'addresses');

function setIgnored(row: NotFoundRow, ignored: boolean): void {
    router.post(ignore.url(row.id), { ignored }, { preserveScroll: true });
}

async function deleteOne(row: NotFoundRow): Promise<void> {
    const answer = await confirm({
        title: 'Delete this address?',
        subject: row.path,
        description:
            'It is removed from the list for good — there is no Recycle bin for the 404 monitor. If a visitor asks for it again, it appears again.',
        confirmLabel: 'Delete address',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

async function deleteMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${noun(selection.count)}?`,
        description:
            'They are removed from the list for good. An address appears again if a visitor asks for it again.',
        confirmLabel: `Delete ${noun(selection.count)}`,
        destructive: true,
        typeToConfirm:
            selection.count > 20 ? `DELETE ${selection.count}` : undefined,
    });

    if (answer) {
        postBulk('delete', selection);
    }
}

function postBulk(
    action: 'ignore' | 'unignore' | 'delete',
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

function rowActions(row: NotFoundRow): DataTableRowAction[] {
    return [
        {
            id: 'redirect',
            label: 'Create redirect',
            icon: CornerUpRight,
            hidden: !row.can.manage,
            onSelect: () => createRedirect(row),
        },
        {
            id: 'open',
            label: 'Open the address',
            icon: ExternalLink,
            onSelect: () => window.open(row.test_url, '_blank', 'noopener'),
        },
        {
            id: 'ignore',
            label: row.is_ignored ? 'Stop ignoring' : 'Ignore',
            icon: row.is_ignored ? Undo2 : EyeOff,
            hidden: !row.can.manage,
            onSelect: () => setIgnored(row, !row.is_ignored),
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !row.can.manage,
            onSelect: () => void deleteOne(row),
        },
    ];
}

const bulkActions = computed<DataTableBulkAction[]>(() =>
    props.canManage
        ? [
              {
                  id: 'ignore',
                  label: 'Ignore',
                  icon: EyeOff,
                  run: (selection) => postBulk('ignore', selection),
              },
              {
                  id: 'unignore',
                  label: 'Stop ignoring',
                  icon: Undo2,
                  run: (selection) => postBulk('unignore', selection),
              },
              {
                  id: 'delete',
                  label: 'Delete',
                  icon: Trash2,
                  destructive: true,
                  run: (selection) => void deleteMany(selection),
              },
          ]
        : [],
);

// CRUD-02: Open · Ignored · All, as links that set `filter[status]`.
const page = usePage();
const currentStatus = computed(
    () =>
        new URLSearchParams(page.url.split('?')[1] ?? '').get(
            'filter[status]',
        ) ?? '',
);

const statusTabs = computed<ListTab[]>(() => {
    const options = props.filterOptions.status ?? [];
    const total = options.reduce((sum, option) => sum + (option.count ?? 0), 0);
    const tab = (key: string, label: string, count: number): ListTab => ({
        key,
        label,
        count,
        // A bare address opens the Open tab, so "All" carries the default sort explicitly.
        href:
            key === ''
                ? notFoundIndex.url({ query: { sort: '-hits' } })
                : `${notFoundIndex.url()}?filter[status]=${key}`,
        active: currentStatus.value === key,
    });

    return [
        ...options.map((option) =>
            tab(option.value, option.label, option.count ?? 0),
        ),
        tab('', 'All', total),
    ];
});
</script>

<template>
    <Head title="404 monitor" />

    <ListPageHeader
        title="404 monitor"
        :description="`Addresses visitors asked for that do not exist, most asked first. Entries unseen for ${retentionDays} days are removed.`"
        :tabs="statusTabs"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="ignoreOpen = true">
                <ListFilter aria-hidden="true" />
                Ignore list
            </Button>
        </template>
        <template #tabs>
            <SeoTabs current="not-found" />
        </template>
    </ListPageHeader>

    <DataTable
        table-key="not-found-logs"
        noun="404"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search address or referrer"
        :row-label="(row: NotFoundRow) => row.path"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        empty-title="No missing addresses"
        empty-description="When a visitor opens an address that does not exist, it is listed here with how often it happened, so you can send it to the right page."
    >
        <template #cell-path="{ row, search }">
            <span class="font-mono text-[13px] break-all">
                <HighlightText :text="row.path" :term="search" />
            </span>
        </template>
        <template #cell-hits="{ row }">
            <span class="tabular-nums">{{
                row.hits.toLocaleString('en-GB')
            }}</span>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge
                v-if="row.redirect"
                :label="
                    row.redirect.code === 410
                        ? 'Gone (410)'
                        : `Redirected (${row.redirect.code})`
                "
                tone="success"
            />
            <StatusBadge
                v-else-if="row.is_ignored"
                label="Ignored"
                tone="neutral"
            />
            <StatusBadge v-else label="Open" tone="warning" />
        </template>
        <template #cell-referrer="{ row, search }">
            <span
                v-if="row.referrer"
                class="block max-w-64 truncate"
                :title="row.referrer"
            >
                <HighlightText :text="row.referrer" :term="search" />
            </span>
            <span v-else class="text-muted-foreground">Direct or unknown</span>
        </template>
        <template #cell-last_seen_at="{ row }">
            <span class="tabular-nums">{{
                formatDateTime(row.last_seen_at)
            }}</span>
            <span class="ml-2 text-xs text-muted-foreground">{{
                timeAgo(row.last_seen_at)
            }}</span>
        </template>
        <template #cell-first_seen_at="{ row }">
            <span class="tabular-nums">{{
                formatDateTime(row.first_seen_at).split(' · ')[0]
            }}</span>
        </template>
    </DataTable>

    <RedirectDialog v-model:open="dialogOpen" :draft="draft" />
    <IgnoreListDialog v-model:open="ignoreOpen" :list="ignoreList" />
</template>
