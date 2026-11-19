<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Copy,
    ExternalLink,
    Eye,
    FileUser,
    Lock,
    Pencil,
    Plus,
    Trash2,
    Unlock,
} from '@lucide/vue';
import { computed } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { formatDate } from '@/lib/format';
import { index as applicationsIndex } from '@/routes/admin/applications';
import {
    bulk,
    create,
    destroy,
    duplicate,
    index as careersIndex,
    preview,
    status as setStatus,
} from '@/routes/admin/careers';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    JobPostRow,
    JobStatusValue,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// ADM-SCR-10 Careers › Jobs. Row verbs live in the ⋯ menu (DEC-75). A job post is Draft, Open or Closed; Open
// roles close by themselves at 00:05 the day after the deadline (FR-CAR-003). Delete goes to the Recycle bin
// with Undo (DEL-02); the applications of a deleted role stay in Applications.
const props = defineProps<{
    rows: JobPostRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canCreate: boolean;
    canShareViews: boolean;
    canApplications: boolean;
    binCount: number;
}>();

const columns: DataTableColumn[] = [
    { id: 'title', label: 'Role', required: true, sortable: true, width: 340 },
    { id: 'department', label: 'Department', sortable: true, width: 170 },
    { id: 'office', label: 'Location', width: 170 },
    { id: 'type', label: 'Type', width: 120 },
    { id: 'status', label: 'Status', sortable: true, width: 130 },
    { id: 'deadline', label: 'Deadline', sortable: true, width: 140 },
    {
        id: 'applications_count',
        label: 'Applications',
        sortable: true,
        width: 130,
    },
    {
        id: 'updated_at',
        label: 'Updated',
        sortable: true,
        width: 190,
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
    {
        id: 'department',
        label: 'Department',
        kind: 'enum',
        options: props.filterOptions.department,
    },
    {
        id: 'office',
        label: 'Location',
        kind: 'enum',
        options: props.filterOptions.office,
    },
    {
        id: 'type',
        label: 'Type',
        kind: 'enum',
        options: props.filterOptions.type,
    },
    { id: 'deadline', label: 'Deadline', kind: 'date' },
]);

const tone = {
    draft: 'neutral',
    open: 'success',
    closed: 'warning',
} as const;

/** The longer sentence behind a status badge (HELP-06). */
function statusHint(row: JobPostRow): string | undefined {
    if (row.deadline_passed) {
        return 'Open. The deadline has passed, so the role takes no more applications and closes at 00:05.';
    }

    if (row.status.value === 'open') {
        return `Open. Takes applications until ${formatDate(row.deadline)}.`;
    }

    if (row.status.value === 'closed' && row.closed_at) {
        return `Closed on ${formatDateTime(row.closed_at).split(' · ')[0]}. The page stays up for 30 days, then it is gone.`;
    }

    return row.status.value === 'draft'
        ? 'Draft. Not on the website until you open it.'
        : undefined;
}

function openRow(row: JobPostRow): void {
    if (row.can.edit) {
        router.visit(row.edit_url);
    }
}

const confirm = useConfirm();

async function deleteOne(row: JobPostRow): Promise<void> {
    const live = row.status.value !== 'draft';
    const count = row.applications_count;
    const answer = await confirm({
        title: 'Delete this job post?',
        subject: row.title,
        description:
            (live
                ? 'It leaves the website at once: its address shows “page not found”. '
                : '') +
            (count > 0
                ? `Its ${count} ${count === 1 ? 'application stays' : 'applications stay'} under Applications. `
                : '') +
            'The post moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete job post',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

function moveTo(row: JobPostRow, status: JobStatusValue): void {
    router.patch(setStatus.url(row.id), { status }, { preserveScroll: true });
}

function rowActions(row: JobPostRow): DataTableRowAction[] {
    const { value } = row.status;

    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => router.visit(row.edit_url),
        },
        {
            id: 'view',
            label: 'View on site',
            icon: ExternalLink,
            hidden: row.public_url === null,
            onSelect: () =>
                window.open(row.public_url ?? '', '_blank', 'noopener'),
        },
        {
            id: 'preview',
            label: 'Preview',
            icon: Eye,
            hidden: row.public_url !== null,
            // HELP-04: a signed 60-minute link, so a draft can be read before it is opened.
            onSelect: () =>
                window.open(preview.url(row.id), '_blank', 'noopener'),
        },
        {
            id: 'applications',
            label: `Applications (${row.applications_count})`,
            icon: FileUser,
            hidden: !row.can.applications || row.applications_count === 0,
            onSelect: () =>
                router.visit(
                    applicationsIndex.url({
                        query: { 'filter[job]': String(row.id) },
                    }),
                ),
        },
        {
            id: 'open',
            label: 'Open for applications',
            icon: Unlock,
            hidden: value === 'open' || !row.can.edit,
            onSelect: () => moveTo(row, 'open'),
        },
        {
            id: 'close',
            label: 'Close applications',
            icon: Lock,
            hidden: value !== 'open' || !row.can.edit,
            onSelect: () => moveTo(row, 'closed'),
        },
        {
            id: 'duplicate',
            label: 'Duplicate',
            icon: Copy,
            hidden: !row.can.duplicate,
            onSelect: () => router.post(duplicate.url(row.id)),
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

function postBulk(action: 'close' | 'delete', selection: TableSelection): void {
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

async function deleteMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} job ${selection.count === 1 ? 'post' : 'posts'}?`,
        description:
            'They leave the website at once. They move to the Recycle bin and can be restored as drafts for 90 days; their applications stay.',
        confirmLabel: 'Delete job posts',
        destructive: true,
        typeToConfirm:
            selection.count > 20 ? `DELETE ${selection.count}` : undefined,
    });

    if (answer) {
        postBulk('delete', selection);
    }
}

const bulkActions = computed<DataTableBulkAction[]>(() =>
    props.canCreate
        ? [
              {
                  id: 'close',
                  label: 'Close applications',
                  icon: Lock,
                  run: (selection) => postBulk('close', selection),
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
                ? careersIndex.url()
                : `${careersIndex.url()}?filter[status]=${key}`,
        active: currentStatus.value === key,
    });

    return [
        tab('', 'All', total),
        ...options.map((option) =>
            tab(option.value, option.label, option.count ?? 0),
        ),
    ];
});
</script>

<template>
    <Head title="Careers" />

    <ListPageHeader
        title="Careers"
        description="The roles on the careers page. Open roles take applications until their deadline; drafts stay hidden until you open them."
        :tabs="statusTabs"
        :bin-count="binCount"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'job_post' } })"
    >
        <template v-if="canCreate" #actions>
            <Button as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New job post
                </Link>
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="job_posts"
        noun="job post"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search role, department or URL"
        :row-label="(row: JobPostRow) => row.title"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canCreate"
        clickable-rows
        empty-title="No job posts yet"
        empty-description="Job posts appear on the careers page once they are open. Add the first role."
        @row-click="openRow"
    >
        <template #cell-title="{ row, search }">
            <span class="min-w-0">
                <span class="block truncate font-medium"
                    ><HighlightText :text="row.title" :term="search"
                /></span>
                <span
                    class="block truncate font-mono text-xs text-muted-foreground"
                    >{{ row.path }}</span
                >
            </span>
        </template>
        <template #cell-department="{ row, search }">
            <HighlightText :text="row.department" :term="search" />
        </template>
        <template #cell-office="{ row }">{{
            row.office ?? 'Project sites'
        }}</template>
        <template #cell-type="{ row }">{{
            row.employment_type.label
        }}</template>
        <template #cell-status="{ row }">
            <!-- HELP-06: plain words plus the detail in a tooltip; colour is never the only signal. -->
            <span :title="statusHint(row)">
                <StatusBadge
                    :label="
                        row.deadline_passed
                            ? 'Open · deadline passed'
                            : row.status.label
                    "
                    :tone="
                        row.deadline_passed ? 'warning' : tone[row.status.value]
                    "
                />
            </span>
        </template>
        <template #cell-deadline="{ row }">
            <span class="tabular-nums">{{ formatDate(row.deadline) }}</span>
        </template>
        <template #cell-applications_count="{ row }">
            <Link
                v-if="canApplications && row.applications_count > 0"
                :href="
                    applicationsIndex.url({
                        query: { 'filter[job]': String(row.id) },
                    })
                "
                class="tabular-nums underline-offset-4 hover:underline"
                @click.stop
            >
                {{ row.applications_count }}
                <span
                    v-if="row.new_applications_count > 0"
                    class="ml-1 text-xs text-primary"
                    >{{ row.new_applications_count }} new</span
                >
            </Link>
            <span v-else class="text-muted-foreground tabular-nums">{{
                row.applications_count
            }}</span>
        </template>
        <template #cell-updated_at="{ row }">
            <template v-if="row.updated_at">
                <span class="tabular-nums">{{
                    formatDateTime(row.updated_at).split(' · ')[0]
                }}</span>
                <span class="ml-2 text-xs text-muted-foreground">{{
                    timeAgo(row.updated_at)
                }}</span>
            </template>
        </template>
    </DataTable>
</template>
