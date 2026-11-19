<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Download, ExternalLink, Trash2, UserCheck, UserX } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import ApplicationDrawer from '@/components/admin/careers/ApplicationDrawer.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { formatPhone } from '@/lib/format';
import { bulk, cv, destroy, status } from '@/routes/admin/applications';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    ApplicationStatusOption,
    ApplicationStatusValue,
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    JobApplicationDetail,
    JobApplicationRow,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// ADM-SCR-10 Applications (FR-CAR-005). Everything here is personal data, so the whole screen needs the
// applications permission. A row opens the drawer; row verbs live in the ⋯ menu (DEC-75). The CV is an
// authorised download. Delete goes to the Recycle bin for 365 days with Undo; an application nobody has
// touched for 12 months is removed with its CV.
const props = defineProps<{
    rows: JobApplicationRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canShareViews: boolean;
    binCount: number;
    statuses: ApplicationStatusOption[];
    openId: number | null;
    initial: JobApplicationDetail | null;
}>();

const columns: DataTableColumn[] = [
    {
        id: 'name',
        label: 'Applicant',
        required: true,
        sortable: true,
        width: 280,
    },
    { id: 'job', label: 'Role', width: 240 },
    {
        id: 'current_position',
        label: 'Current position',
        width: 200,
        hidden: true,
    },
    { id: 'years_experience', label: 'Experience', sortable: true, width: 120 },
    { id: 'status', label: 'Status', sortable: true, width: 140 },
    { id: 'created_at', label: 'Received', sortable: true, width: 190 },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'status',
        label: 'Status',
        kind: 'enum',
        options: props.filterOptions.status,
    },
    {
        id: 'job',
        label: 'Role',
        kind: 'enum',
        options: props.filterOptions.job,
    },
    { id: 'review', label: 'Needs review', kind: 'boolean' },
    { id: 'received', label: 'Received', kind: 'date' },
]);

const tone: Record<ApplicationStatusValue, string> = {
    new: 'warning',
    shortlisted: 'info',
    interview: 'info',
    hired: 'success',
    rejected: 'neutral',
};

// --- drawer ----------------------------------------------------------------------------------------------
const page = usePage();
const activeId = ref<number | null>(props.openId);
const drawerOpen = ref(props.openId !== null);

function openDrawer(row: { id: number }): void {
    activeId.value = row.id;
    drawerOpen.value = true;
}

// A pasted `?application=` link opens the drawer; closing it takes the parameter out of the address.
watch(drawerOpen, (isOpen) => {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    if (!isOpen && params.has('application')) {
        params.delete('application');
        const rest = params.toString();

        router.replace({
            url: path + (rest ? `?${rest}` : ''),
            preserveScroll: true,
            preserveState: true,
        });
    }
});

// --- row and bulk actions -----------------------------------------------------------------------------
const confirm = useConfirm();

function moveTo(row: JobApplicationRow, to: ApplicationStatusValue): void {
    router.patch(status.url(row.id), { status: to }, { preserveScroll: true });
}

async function deleteOne(row: JobApplicationRow): Promise<void> {
    const answer = await confirm({
        title: 'Delete this application?',
        subject: row.name,
        description:
            'The application and its CV move to the Recycle bin. You can restore them for 365 days.',
        confirmLabel: 'Delete application',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

function rowActions(row: JobApplicationRow): DataTableRowAction[] {
    return [
        {
            id: 'open',
            label: 'Open',
            icon: ExternalLink,
            onSelect: () => openDrawer(row),
        },
        {
            id: 'cv',
            label: 'Download CV',
            icon: Download,
            onSelect: () => {
                window.location.href = cv.url(row.id);
            },
        },
        ...props.statuses
            .filter((item) => item.value !== row.status.value)
            .map((item): DataTableRowAction => ({
                id: `status-${item.value}`,
                label: `Mark as ${item.label.toLowerCase()}`,
                icon: item.value === 'rejected' ? UserX : UserCheck,
                hidden: !row.can.update,
                onSelect: () => moveTo(row, item.value),
            })),
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

function postBulk(
    selection: TableSelection,
    body: { action: 'status' | 'delete'; status?: ApplicationStatusValue },
): void {
    router.post(
        bulk.url(),
        {
            ...body,
            ...(selection.all
                ? { all: true, ...selection.query }
                : { ids: selection.ids }),
        },
        { preserveScroll: true },
    );
}

async function deleteMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${selection.count === 1 ? 'application' : 'applications'}?`,
        description:
            'They move to the Recycle bin with their CVs. You can restore them for 365 days.',
        confirmLabel: 'Delete applications',
        destructive: true,
        typeToConfirm:
            selection.count > 20 ? `DELETE ${selection.count}` : undefined,
    });

    if (answer) {
        postBulk(selection, { action: 'delete' });
    }
}

const bulkActions = computed<DataTableBulkAction[]>(() => [
    {
        id: 'shortlist',
        label: 'Shortlist',
        icon: UserCheck,
        run: (selection) =>
            postBulk(selection, { action: 'status', status: 'shortlisted' }),
    },
    {
        id: 'reject',
        label: 'Reject',
        icon: UserX,
        run: (selection) =>
            postBulk(selection, { action: 'status', status: 'rejected' }),
    },
    {
        id: 'delete',
        label: 'Delete',
        icon: Trash2,
        destructive: true,
        run: (selection) => void deleteMany(selection),
    },
]);
</script>

<template>
    <Head title="Applications" />

    <ListPageHeader
        title="Applications"
        description="CVs sent through the careers pages. Open one to read it, download the CV and move it along the hiring steps. Applications are removed 12 months after their last change."
        :bin-count="binCount"
        :bin-href="
            binIndex.url({ query: { 'filter[type]': 'job_application' } })
        "
    />

    <DataTable
        table-key="job_applications"
        noun="application"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search name, email, phone or role"
        :row-label="(row: JobApplicationRow) => row.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        selectable
        clickable-rows
        empty-title="No applications yet"
        empty-description="Applications from the careers page appear here, newest first."
        @row-click="openDrawer"
    >
        <template #cell-name="{ row, search }">
            <span class="min-w-0">
                <span class="flex items-center gap-1.5 font-medium">
                    <span class="truncate"
                        ><HighlightText :text="row.name" :term="search"
                    /></span>
                    <span
                        v-if="row.needs_review"
                        class="shrink-0 rounded-sm bg-warning-50 px-1.5 py-0.5 text-xs font-medium text-warning-700"
                        :title="row.review_label ?? undefined"
                        >Needs review</span
                    >
                </span>
                <span class="block truncate text-xs text-muted-foreground"
                    ><HighlightText :text="row.email" :term="search" /> ·
                    <span class="tabular-nums">{{
                        formatPhone(row.phone)
                    }}</span></span
                >
            </span>
        </template>
        <template #cell-job="{ row }">
            <span v-if="row.job">{{ row.job.title }}</span>
            <span v-else class="text-muted-foreground">General CV</span>
        </template>
        <template #cell-current_position="{ row, search }">
            <HighlightText
                v-if="row.current_position"
                :text="row.current_position"
                :term="search"
            />
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-years_experience="{ row }">
            <span v-if="row.years_experience !== null" class="tabular-nums"
                >{{ row.years_experience }}
                {{ row.years_experience === 1 ? 'year' : 'years' }}</span
            >
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-status="{ row }">
            <StatusBadge
                :label="row.status.label"
                :tone="tone[row.status.value]"
            />
        </template>
        <template #cell-created_at="{ row }">
            <template v-if="row.created_at">
                <span class="tabular-nums">{{
                    formatDateTime(row.created_at).split(' · ')[0]
                }}</span>
                <span class="ml-2 text-xs text-muted-foreground">{{
                    timeAgo(row.created_at)
                }}</span>
            </template>
        </template>
    </DataTable>

    <ApplicationDrawer
        v-model:open="drawerOpen"
        :application-id="activeId"
        :initial="initial"
    />
</template>
