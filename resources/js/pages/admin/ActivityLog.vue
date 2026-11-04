<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { causers } from '@/routes/admin/activity-log';
import type {
    ActivityRow,
    DataTableColumn,
    FilterDef,
    FilterOption,
    TableMeta,
    TableViewItem,
} from '@/types';

const props = defineProps<{
    rows: ActivityRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canViewAll: boolean;
    canShareViews: boolean;
}>();

// ADM-SCR-17 / design/admin.md §12.3: When, Who, Action, What, IP. Read-only (ADM-LOG-004).
const columns: DataTableColumn[] = [
    {
        id: 'created_at',
        label: 'When',
        required: true,
        sortable: true,
        width: 270,
    },
    { id: 'who', label: 'Who', width: 240 },
    {
        id: 'action',
        label: 'Action',
        sortable: true,
        sortKey: 'event',
        width: 160,
    },
    { id: 'description', label: 'What', sortable: true, width: 340 },
    { id: 'ip', label: 'IP address', width: 150 },
    {
        id: 'log',
        label: 'Area',
        sortable: true,
        sortKey: 'log_name',
        hidden: true,
        width: 130,
    },
    { id: 'changes', label: 'Changes', hidden: true, width: 300 },
];

const filters = computed<FilterDef[]>(() => [
    { id: 'when', label: 'When', kind: 'date' },
    // Someone who only sees their own actions has nobody to filter by.
    ...(props.canViewAll
        ? [
              {
                  id: 'causer',
                  label: 'Who',
                  kind: 'relation' as const,
                  optionsUrl: causers.url(),
              },
          ]
        : []),
    {
        id: 'log',
        label: 'Area',
        kind: 'enum',
        options: props.filterOptions.log,
    },
    {
        id: 'event',
        label: 'Action',
        kind: 'enum',
        options: props.filterOptions.event,
    },
    {
        id: 'ip',
        label: 'IP address',
        kind: 'text',
        placeholder: 'Starts with or contains…',
    },
]);

function parts(iso: string): { date: string; time: string } {
    const [date, time] = formatDateTime(iso).split(' · ');

    return { date, time };
}
</script>

<template>
    <Head title="Activity log" />

    <AdminPageHeader
        title="Activity log"
        :description="
            canViewAll
                ? 'Who changed what, and when. Entries are kept for a year and cannot be edited or deleted.'
                : 'What you did, and when. Entries are kept for a year and cannot be edited or deleted.'
        "
    />

    <DataTable
        table-key="activity-log"
        noun="entry"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search action, person, description or IP"
        :row-label="
            (row: ActivityRow) =>
                `${row.action.label} ${row.created_at ? formatDateTime(row.created_at) : ''}`
        "
        expandable
        empty-title="Nothing recorded yet"
        empty-description="Sign-ins and changes made in the admin appear here as they happen."
    >
        <template #cell-created_at="{ row }">
            <template v-if="row.created_at">
                <span class="tabular-nums"
                    >{{ parts(row.created_at).date }} ·
                    {{ parts(row.created_at).time }}</span
                >
                <span class="ml-2 text-xs text-muted-foreground">{{
                    timeAgo(row.created_at)
                }}</span>
            </template>
        </template>
        <template #cell-who="{ row }">
            <div v-if="row.who" class="min-w-0 leading-tight">
                <p class="truncate font-medium">
                    {{ row.who.name
                    }}<span
                        v-if="row.who.deleted"
                        class="font-normal text-muted-foreground"
                    >
                        (removed)</span
                    >
                </p>
                <p
                    v-if="row.who.role"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{ row.who.role }}
                </p>
            </div>
            <span v-else class="text-muted-foreground">Not signed in</span>
        </template>
        <template #cell-action="{ row }">
            <StatusBadge :label="row.action.label" :tone="row.action.tone" />
        </template>
        <template #cell-description="{ row, search }">
            <HighlightText :text="row.description ?? ''" :term="search" />
            <span v-if="row.subject" class="text-muted-foreground">
                · {{ row.subject.label }}
            </span>
        </template>
        <template #cell-ip="{ row, search }">
            <HighlightText
                v-if="row.ip"
                :text="row.ip"
                :term="search"
                class="tabular-nums"
            />
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-changes="{ row }">
            <span v-if="row.changes.length">
                {{
                    row.changes
                        .map(
                            (change) =>
                                `${change.field}: ${change.old ?? '–'} → ${change.new ?? '–'}`,
                        )
                        .join('; ')
                }}
            </span>
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-log="{ row }">
            <span class="capitalize">{{ row.log }}</span>
        </template>

        <template #expanded="{ row }">
            <div class="grid gap-5 text-sm md:grid-cols-2">
                <div>
                    <h3
                        class="mb-2 text-xs font-semibold tracking-[0.06em] text-muted-foreground uppercase"
                    >
                        Changes
                    </h3>
                    <table v-if="row.changes.length" class="w-full text-left">
                        <thead class="sr-only">
                            <tr>
                                <th>Field</th>
                                <th>Before</th>
                                <th>After</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="change in row.changes"
                                :key="change.field"
                                class="border-b last:border-0"
                            >
                                <th
                                    scope="row"
                                    class="py-1.5 pr-3 align-top font-medium"
                                >
                                    {{ change.field }}
                                </th>
                                <td class="py-1.5 pr-3 align-top">
                                    <del
                                        v-if="change.old !== null"
                                        class="rounded-xs bg-error-50 px-1 text-error-700"
                                        >{{ change.old }}</del
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >–</span
                                    >
                                </td>
                                <td class="py-1.5 align-top">
                                    <ins
                                        v-if="change.new !== null"
                                        class="rounded-xs bg-success-50 px-1 text-success-700 no-underline"
                                        >{{ change.new }}</ins
                                    >
                                    <span v-else class="text-muted-foreground"
                                        >–</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="text-muted-foreground">
                        This entry records an event, not a change to a field.
                    </p>
                </div>
                <div>
                    <h3
                        class="mb-2 text-xs font-semibold tracking-[0.06em] text-muted-foreground uppercase"
                    >
                        Details
                    </h3>
                    <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-1.5">
                        <template
                            v-for="detail in row.details"
                            :key="detail.key"
                        >
                            <dt class="text-muted-foreground">
                                {{ detail.key }}
                            </dt>
                            <dd class="break-words">{{ detail.value }}</dd>
                        </template>
                        <template v-if="row.subject">
                            <dt class="text-muted-foreground">
                                {{ row.subject.type }}
                            </dt>
                            <dd>
                                {{ row.subject.label
                                }}<span
                                    v-if="row.subject.missing"
                                    class="text-muted-foreground"
                                >
                                    (removed)</span
                                >
                            </dd>
                        </template>
                        <template v-if="row.ip">
                            <dt class="text-muted-foreground">IP address</dt>
                            <dd class="tabular-nums">{{ row.ip }}</dd>
                        </template>
                        <template v-if="row.user_agent">
                            <dt class="text-muted-foreground">Browser</dt>
                            <dd class="break-words">{{ row.user_agent }}</dd>
                        </template>
                    </dl>
                </div>
            </div>
        </template>
    </DataTable>
</template>
