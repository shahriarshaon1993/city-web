<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Copy,
    ExternalLink,
    MessageCircle,
    PhoneCall,
    Plus,
    Trash2,
    UserRoundPlus,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import LeadAssignDialog from '@/components/admin/leads/LeadAssignDialog.vue';
import LeadDrawer from '@/components/admin/leads/LeadDrawer.vue';
import LeadReasonDialog from '@/components/admin/leads/LeadReasonDialog.vue';
import LeadStatusBadge from '@/components/admin/leads/LeadStatusBadge.vue';
import LeadViewSwitch from '@/components/admin/leads/LeadViewSwitch.vue';
import { Button } from '@/components/ui/button';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { useLeadStatusChange } from '@/composables/useLeadActions';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { notify } from '@/lib/toast';
import { cn } from '@/lib/utils';
import {
    bulk,
    create,
    destroy,
    index as leadsIndex,
} from '@/routes/admin/leads';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    LeadDetail,
    LeadRow,
    LeadsShared,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// ADM-SCR-11 Leads, table view (FR-LEAD-009). One DataTable (DEC-34) with the three saved views
// the page ships with, the detail drawer on a row click, status moves through the shared
// reason dialog, the "New lead" dialog (DEC-33) and the export (phones stay text, FR-LEAD-010).
const props = defineProps<
    LeadsShared & {
        rows: LeadRow[];
        meta: TableMeta;
        views: TableViewItem[];
        filterOptions: Record<string, FilterOption[]>;
    }
>();

const { can } = useCan();
const page = usePage();
const confirm = useConfirm();

const columns: DataTableColumn[] = [
    { id: 'created_at', label: 'Received', sortable: true, width: 190 },
    { id: 'name', label: 'Name', sortable: true, required: true, width: 220 },
    { id: 'phone', label: 'Phone', width: 200 },
    { id: 'type', label: 'Type', sortable: true, width: 140 },
    { id: 'project', label: 'Project', width: 180 },
    { id: 'status', label: 'Status', sortable: true, width: 170 },
    { id: 'assignee', label: 'Assignee', width: 150 },
    { id: 'follow_up_at', label: 'Follow-up', sortable: true, width: 170 },
    { id: 'source', label: 'Source', width: 160 },
    {
        id: 'touches_count',
        label: 'Asked',
        sortable: true,
        width: 90,
        align: 'right',
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
        id: 'stage',
        label: 'Open or closed',
        kind: 'enum',
        options: props.filterOptions.stage,
    },
    {
        id: 'type',
        label: 'Type',
        kind: 'enum',
        options: props.filterOptions.type,
    },
    {
        id: 'project',
        label: 'Project',
        kind: 'enum',
        options: props.filterOptions.project,
    },
    ...(props.filterOptions.assignee
        ? [
              {
                  id: 'assignee',
                  label: 'Assignee',
                  kind: 'enum' as const,
                  options: props.filterOptions.assignee,
              },
          ]
        : []),
    {
        id: 'follow_up',
        label: 'Follow-up',
        kind: 'enum',
        options: props.filterOptions.follow_up,
    },
    { id: 'created', label: 'Received', kind: 'date' },
]);

// --- drawer ------------------------------------------------------------------------------------------
const activeId = ref<number | null>(props.openLead?.id ?? null);
const drawerOpen = ref(props.openLead !== null);

function openDrawer(row: { id: number }): void {
    activeId.value = row.id;
    drawerOpen.value = true;
}

// A pasted `?lead=` link opens the drawer; closing it takes the parameter out of the address.
watch(drawerOpen, (isOpen) => {
    const [path, query = ''] = page.url.split('?');
    const params = new URLSearchParams(query);

    if (!isOpen && params.has('lead')) {
        params.delete('lead');
        const rest = params.toString();

        router.replace({
            url: path + (rest ? `?${rest}` : ''),
            preserveScroll: true,
            preserveState: true,
        });
    }
});

// --- status (FR-LEAD-006) ---------------------------------------------------------------------------
const { reasonRequest, reasonOpen, move } = useLeadStatusChange();

function onMove(lead: LeadDetail, to: LeadRow['status']): void {
    move(lead, to);
}

// --- new lead (DEC-33) -------------------------------------------------------------------------------

// --- phone helpers -------------------------------------------------------------------------------------
async function copyPhone(row: LeadRow): Promise<void> {
    try {
        await navigator.clipboard.writeText(row.phone);
        notify.success('Phone number copied', { description: row.name });
    } catch {
        notify.error('Could not copy the number', {
            description: 'Select it and copy by hand.',
        });
    }
}

const whatsappLink = (row: LeadRow): string =>
    `https://wa.me/${(row.whatsapp ?? row.phone).replace(/^\+/, '')}`;

// --- row and bulk actions ---------------------------------------------------------------------------------
function rowActions(row: LeadRow): DataTableRowAction[] {
    return [
        {
            id: 'open',
            label: 'Open',
            icon: ExternalLink,
            onSelect: () => openDrawer(row),
        },
        {
            id: 'call',
            label: 'Call',
            icon: PhoneCall,
            hidden: !row.contact_visible,
            onSelect: () => {
                window.location.href = `tel:${row.phone}`;
            },
        },
        {
            id: 'whatsapp',
            label: 'WhatsApp',
            icon: MessageCircle,
            hidden: !row.contact_visible,
            onSelect: () => {
                window.open(whatsappLink(row), '_blank', 'noopener');
            },
        },
        {
            id: 'contacted',
            label: 'Mark as contacted',
            hidden: !row.can.update || row.status === 'contacted',
            onSelect: () => move(row, 'contacted'),
        },
        {
            id: 'won',
            label: 'Mark as won…',
            hidden: !row.can.update || row.status === 'won',
            onSelect: () => move(row, 'won'),
        },
        {
            id: 'lost',
            label: 'Mark as lost…',
            hidden: !row.can.update || row.status === 'lost',
            onSelect: () => move(row, 'lost'),
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

const noun = (count: number): string => (count === 1 ? 'lead' : 'leads');

async function deleteOne(row: LeadRow): Promise<void> {
    const answer = await confirm({
        title: 'Delete this lead?',
        subject: row.name,
        description:
            'The lead and its timeline move to the Recycle bin. You can restore them for 365 days.',
        confirmLabel: 'Delete lead',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

const assignOpen = ref(false);
const assignSelection = ref<TableSelection | null>(null);

function postBulk(
    action: 'assign' | 'delete',
    selection: TableSelection,
    assigneeId: number | null = null,
): void {
    router.post(
        bulk.url(),
        {
            action,
            ...(action === 'assign' ? { assignee_id: assigneeId } : {}),
            ...(selection.all
                ? { all: true, ...selection.query }
                : { ids: selection.ids }),
        },
        { preserveScroll: true },
    );
}

async function deleteMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${noun(selection.count)}?`,
        description:
            'The leads and their timelines move to the Recycle bin. You can restore them for 365 days.',
        confirmLabel: `Delete ${noun(selection.count)}`,
        destructive: true,
        typeToConfirm:
            selection.count > 20 ? `DELETE ${selection.count}` : undefined,
    });

    if (answer) {
        postBulk('delete', selection);
    }
}

const bulkActions = computed<DataTableBulkAction[]>(() => [
    ...(props.canAssign
        ? [
              {
                  id: 'assign',
                  label: 'Assign…',
                  icon: UserRoundPlus,
                  run: (selection: TableSelection) => {
                      assignSelection.value = selection;
                      assignOpen.value = true;
                  },
              },
          ]
        : []),
    ...(can('leads.delete')
        ? [
              {
                  id: 'delete',
                  label: 'Delete',
                  icon: Trash2,
                  destructive: true,
                  run: (selection: TableSelection) =>
                      void deleteMany(selection),
              },
          ]
        : []),
]);

const selectable = computed(() => bulkActions.value.length > 0);
</script>

<template>
    <Head title="Leads" />

    <ListPageHeader
        title="Leads"
        description="Enquiries from the website and the ones you add by hand. Open one to call, take notes and set a follow-up."
        :bin-count="can('leads.delete') ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'lead' } })"
    >
        <template v-if="canCreate" #actions>
            <Button as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New lead
                </Link>
            </Button>
        </template>
        <template #tabs>
            <LeadViewSwitch current="table" />
        </template>
    </ListPageHeader>

    <DataTable
        table-key="leads"
        noun="lead"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        :exportable="canExport"
        search-placeholder="Search name, phone, email or message"
        :row-label="(row: LeadRow) => row.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="selectable"
        clickable-rows
        empty-title="No leads yet"
        empty-description="Leads arrive here when someone fills in a form on the website. You can also add one yourself after a phone call or a walk-in."
        @row-click="openDrawer"
    >
        <template #empty-action>
            <Button v-if="canCreate" size="sm" as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New lead
                </Link>
            </Button>
        </template>

        <template #cell-created_at="{ row }">
            <template v-if="row.created_at">
                <span class="tabular-nums">{{
                    formatDateTime(row.created_at)
                }}</span>
                <span class="ml-2 text-xs text-muted-foreground">{{
                    timeAgo(row.created_at)
                }}</span>
            </template>
        </template>
        <template #cell-name="{ row, search }">
            <button
                type="button"
                class="block max-w-full text-left leading-tight"
                @click.stop="openDrawer(row)"
            >
                <span class="block truncate font-medium">
                    <HighlightText :text="row.name" :term="search" />
                </span>
                <span
                    v-if="row.touches_count > 0"
                    class="block truncate text-xs text-muted-foreground"
                >
                    Asked {{ row.touches_count + 1 }} times
                </span>
            </button>
        </template>
        <template #cell-phone="{ row }">
            <span class="inline-flex items-center gap-1">
                <component
                    :is="row.contact_visible ? 'a' : 'span'"
                    :href="row.contact_visible ? `tel:${row.phone}` : undefined"
                    :class="
                        cn(
                            'tabular-nums',
                            row.contact_visible && 'hover:underline',
                        )
                    "
                    :title="
                        row.contact_visible
                            ? 'Call this number'
                            : 'Hidden for your role'
                    "
                    @click.stop
                    >{{ row.phone }}</component
                >
                <Button
                    v-if="row.contact_visible"
                    variant="ghost"
                    size="icon-sm"
                    class="size-7"
                    :aria-label="`Copy ${row.name}'s phone number`"
                    @click.stop="copyPhone(row)"
                >
                    <Copy aria-hidden="true" />
                </Button>
            </span>
        </template>
        <template #cell-type="{ row }">
            <span class="text-sm">{{ row.type_label }}</span>
        </template>
        <template #cell-project="{ row }">
            <span v-if="row.project">{{ row.project.name }}</span>
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-status="{ row }">
            <LeadStatusBadge :status="row.status" :label="row.status_label" />
        </template>
        <template #cell-assignee="{ row }">
            <span v-if="row.assignee">{{ row.assignee.name }}</span>
            <span v-else class="text-muted-foreground">Unassigned</span>
        </template>
        <template #cell-follow_up_at="{ row }">
            <template v-if="row.follow_up_at">
                <span
                    :class="
                        row.follow_up_overdue
                            ? 'font-medium text-error-700'
                            : 'tabular-nums'
                    "
                >
                    {{ formatDateTime(row.follow_up_at).split(' · ')[0] }}
                </span>
                <span
                    v-if="row.follow_up_overdue"
                    class="ml-2 rounded-full bg-error-50 px-2 py-0.5 text-xs text-error-700"
                    >Overdue</span
                >
            </template>
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-source="{ row }">
            <span class="text-muted-foreground">{{ row.source }}</span>
        </template>
        <template #cell-touches_count="{ row }">
            <span class="tabular-nums">{{ row.touches_count + 1 }}</span>
        </template>
    </DataTable>

    <LeadDrawer
        v-model:open="drawerOpen"
        :lead-id="activeId"
        :initial="openLead"
        :statuses="statuses"
        :assignees="assignees"
        :can-assign="canAssign"
        @move="onMove"
    />
    <LeadReasonDialog
        v-model:open="reasonOpen"
        :request="reasonRequest"
        :reasons="reasons"
    />
    <LeadAssignDialog
        v-model:open="assignOpen"
        :count="assignSelection?.count ?? 0"
        :assignees="assignees"
        @submit="
            (assigneeId) =>
                assignSelection &&
                postBulk('assign', assignSelection, assigneeId)
        "
    />
</template>
