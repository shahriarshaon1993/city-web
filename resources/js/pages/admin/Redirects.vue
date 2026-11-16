<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ExternalLink,
    FileUp,
    Pencil,
    Plus,
    ToggleLeft,
    ToggleRight,
    Trash2,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import RedirectDialog from '@/components/admin/seo/RedirectDialog.vue';
import RedirectImportDialog from '@/components/admin/seo/RedirectImportDialog.vue';
import SeoTabs from '@/components/admin/seo/SeoTabs.vue';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import {
    active as activeRoute,
    bulk,
    destroy,
    index as redirectsIndex,
} from '@/routes/admin/redirects';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    RedirectDraft,
    RedirectRow,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// ADM-SCR-14 Redirects (SEO-TEC-008). A dialog entity (crud.md): the list edits through the
// dialog, the Active switch is inline, Delete goes to the Recycle bin with Undo.
const props = defineProps<{
    rows: RedirectRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    limit: { used: number; max: number };
    binCount: number;
}>();

const columns: DataTableColumn[] = [
    {
        id: 'source',
        label: 'Old address',
        required: true,
        sortable: true,
        width: 280,
    },
    { id: 'target', label: 'Sends visitors to', sortable: true, width: 300 },
    { id: 'code', label: 'Type', sortable: true, width: 110 },
    { id: 'is_active', label: 'Active', sortable: true, width: 120 },
    { id: 'hits', label: 'Hits', sortable: true, width: 110, align: 'right' },
    { id: 'last_hit_at', label: 'Last hit', sortable: true, width: 190 },
    {
        id: 'created_at',
        label: 'Added',
        sortable: true,
        width: 140,
        hidden: true,
    },
    { id: 'created_by', label: 'Added by', width: 160, hidden: true },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'status',
        label: 'Status',
        kind: 'enum',
        options: props.filterOptions.status,
    },
    {
        id: 'code',
        label: 'Type',
        kind: 'enum',
        options: props.filterOptions.code,
    },
    {
        id: 'match',
        label: 'Match',
        kind: 'enum',
        options: props.filterOptions.match,
    },
    { id: 'last_hit', label: 'Last hit', kind: 'date' },
    { id: 'created', label: 'Added', kind: 'date' },
]);

const codeLabel = { 301: '301', 302: '302', 410: '410 Gone' } as const;

// --- dialogs -------------------------------------------------------------------------------------
const dialogOpen = ref(false);
const draft = ref<RedirectDraft | null>(null);
const importOpen = ref(false);

function openDialog(row: RedirectRow | null): void {
    draft.value = row
        ? {
              id: row.id,
              source: row.source,
              target: row.target ?? '',
              code: row.code,
              is_active: row.is_active,
          }
        : null;
    dialogOpen.value = true;
}

function openFromRow(row: RedirectRow): void {
    if (row.can.edit) {
        openDialog(row);
    }
}

// --- inline Active switch (CRUD-01): optimistic, the server's answer wins ----------------------------
const pending = reactive<Record<number, boolean>>({});

function isActive(row: RedirectRow): boolean {
    return pending[row.id] ?? row.is_active;
}

function toggle(row: RedirectRow, value: boolean): void {
    pending[row.id] = value;
    router.patch(
        activeRoute.url(row.id),
        { active: value },
        {
            preserveScroll: true,
            onFinish: () => {
                delete pending[row.id];
            },
        },
    );
}

// --- delete (DEL-02): Recycle bin with Undo ---------------------------------------------------------
const confirm = useConfirm();
const noun = (count: number): string =>
    count === 1 ? 'redirect' : 'redirects';

async function deleteOne(row: RedirectRow): Promise<void> {
    const answer = await confirm({
        title: 'Delete this redirect?',
        subject: row.source,
        description:
            'Visitors who use the old address get the 404 page again. The rule moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete redirect',
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
            'Visitors who use these old addresses get the 404 page again. The rules move to the Recycle bin and can be restored for 90 days.',
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
    action: 'activate' | 'deactivate' | 'delete',
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

function rowActions(row: RedirectRow): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => openDialog(row),
        },
        {
            id: 'toggle',
            label: row.is_active ? 'Switch off' : 'Switch on',
            icon: row.is_active ? ToggleLeft : ToggleRight,
            hidden: !row.can.edit,
            onSelect: () => toggle(row, !row.is_active),
        },
        {
            id: 'test',
            label: 'Try the old address',
            icon: ExternalLink,
            hidden: row.test_url === null,
            onSelect: () =>
                window.open(row.test_url ?? '', '_blank', 'noopener'),
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

const bulkActions = computed<DataTableBulkAction[]>(() =>
    props.canManage
        ? [
              {
                  id: 'activate',
                  label: 'Switch on',
                  icon: ToggleRight,
                  run: (selection) => postBulk('activate', selection),
              },
              {
                  id: 'deactivate',
                  label: 'Switch off',
                  icon: ToggleLeft,
                  run: (selection) => postBulk('deactivate', selection),
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

// CRUD-02: status tabs with counts (links that set `filter[status]`).
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
        href:
            key === ''
                ? redirectsIndex.url()
                : `${redirectsIndex.url()}?filter[status]=${key}`,
        active: currentStatus.value === key,
    });

    return [
        tab('', 'All', total),
        ...options.map((option) =>
            tab(option.value, option.label, option.count ?? 0),
        ),
    ];
});

const atLimit = computed(() => props.limit.used >= props.limit.max);
</script>

<template>
    <Head title="Redirects" />

    <ListPageHeader
        title="Redirects"
        :description="`Send old addresses to their new pages so no link breaks. ${limit.used.toLocaleString('en-GB')} of ${limit.max.toLocaleString('en-GB')} rules used.`"
        :tabs="statusTabs"
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'redirect' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="importOpen = true">
                <FileUp aria-hidden="true" />
                Import CSV
            </Button>
            <Button
                :disabled="atLimit"
                :title="
                    atLimit
                        ? 'You have reached the limit of 2,000 redirects. Delete rules you no longer need.'
                        : undefined
                "
                @click="openDialog(null)"
            >
                <Plus aria-hidden="true" />
                New redirect
            </Button>
        </template>
        <template #tabs>
            <SeoTabs current="redirects" />
        </template>
    </ListPageHeader>

    <DataTable
        table-key="redirects"
        noun="redirect"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search old or new address"
        :row-label="(row: RedirectRow) => row.source"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        :clickable-rows="canManage"
        empty-title="No redirects yet"
        empty-description="Redirects send visitors from an old address to a page that exists, so no link breaks. They run before the site looks for a page. Create the first one, or import a CSV."
        @row-click="openFromRow"
    >
        <template #cell-source="{ row, search }">
            <span class="font-mono text-[13px]">
                <HighlightText :text="row.source" :term="search" />
            </span>
        </template>
        <template #cell-target="{ row, search }">
            <span
                v-if="row.target"
                class="flex items-center gap-1.5 font-mono text-[13px]"
            >
                <ArrowRight
                    class="size-3.5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <HighlightText :text="row.target" :term="search" />
            </span>
            <span v-else class="text-muted-foreground"
                >Nothing — the page is gone</span
            >
        </template>
        <template #cell-code="{ row }">
            <span class="tabular-nums">{{ codeLabel[row.code] }}</span>
        </template>
        <template #cell-is_active="{ row }">
            <Switch
                :model-value="isActive(row)"
                :disabled="!row.can.edit"
                :aria-label="`${row.source} is ${isActive(row) ? 'on' : 'off'}`"
                @click.stop
                @update:model-value="(value: boolean) => toggle(row, value)"
            />
        </template>
        <template #cell-hits="{ row }">
            <span class="tabular-nums">{{
                row.hits.toLocaleString('en-GB')
            }}</span>
        </template>
        <template #cell-last_hit_at="{ row }">
            <template v-if="row.last_hit_at">
                <span class="tabular-nums">{{
                    formatDateTime(row.last_hit_at)
                }}</span>
                <span class="ml-2 text-xs text-muted-foreground">{{
                    timeAgo(row.last_hit_at)
                }}</span>
            </template>
            <span v-else class="text-muted-foreground">Never</span>
        </template>
        <template #cell-created_at="{ row }">
            <span class="tabular-nums">{{
                formatDateTime(row.created_at ?? '').split(' · ')[0]
            }}</span>
        </template>
        <template #cell-created_by="{ row }">
            {{ row.created_by ?? '–' }}
        </template>
    </DataTable>

    <RedirectDialog v-model:open="dialogOpen" :draft="draft" />
    <RedirectImportDialog v-model:open="importOpen" />
</template>
