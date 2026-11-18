<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Copy,
    ExternalLink,
    Eye,
    EyeOff,
    Lock,
    Pencil,
    Plus,
    Trash2,
    Upload,
} from '@lucide/vue';
import { computed } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import {
    create,
    destroy,
    duplicate,
    index as pagesIndex,
} from '@/routes/admin/pages';
import { preview, publish, unpublish } from '@/routes/admin/publishing';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    PageRow,
    TableMeta,
    TableViewItem,
} from '@/types';

// ADM-SCR-06 Pages: the home page, the other system pages, the legal pages and every custom page. Row verbs
// live in the ⋯ menu (DEC-75). A system page is locked: it cannot be deleted or duplicated, and says why
// (HELP-08). Delete goes to the Recycle bin with Undo (DEL-02).
const props = defineProps<{
    rows: PageRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canCreate: boolean;
    canShareViews: boolean;
    binCount: number;
}>();

const columns: DataTableColumn[] = [
    { id: 'title', label: 'Page', required: true, sortable: true, width: 340 },
    { id: 'template', label: 'Template', sortable: true, width: 130 },
    { id: 'blocks_count', label: 'Blocks', sortable: true, width: 100 },
    { id: 'publish_status', label: 'Publishing', sortable: true, width: 150 },
    { id: 'updated_at', label: 'Updated', sortable: true, width: 190 },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'status',
        label: 'Publishing',
        kind: 'enum',
        options: props.filterOptions.status,
    },
    {
        id: 'template',
        label: 'Template',
        kind: 'enum',
        options: props.filterOptions.template,
    },
    { id: 'updated', label: 'Updated', kind: 'date' },
]);

const tone = {
    published: 'success',
    scheduled: 'info',
    archived: 'warning',
    draft: 'neutral',
} as const;

/** The longer sentence behind a status badge (HELP-06). */
function statusHint(row: PageRow): string | undefined {
    const when = row.scheduled_at ? formatDateTime(row.scheduled_at) : null;

    if (row.pending) {
        return when
            ? `Published. Changes are waiting and go live ${when}.`
            : 'Published. Changes are waiting: the live version is older.';
    }

    if (row.status.value === 'scheduled' && when) {
        return `Scheduled. Goes live ${when} (Dhaka time).`;
    }

    return undefined;
}

function openRow(row: PageRow): void {
    if (row.can.edit) {
        router.visit(row.edit_url);
    }
}

const confirm = useConfirm();

async function deleteOne(row: PageRow): Promise<void> {
    const live = row.status.value === 'published';
    const answer = await confirm({
        title: 'Delete this page?',
        subject: row.title,
        description: live
            ? 'It leaves the website: visitors will see “page not found” unless you add a redirect under SEO › Redirects. The page moves to the Recycle bin and can be restored as a draft.'
            : 'The page moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete page',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

function rowActions(row: PageRow): DataTableRowAction[] {
    const live = row.status.value === 'published';

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
            hidden: !live,
            onSelect: () =>
                window.open(row.public_url ?? '', '_blank', 'noopener'),
        },
        {
            id: 'preview',
            label: row.pending ? 'Preview changes' : 'Preview',
            icon: Eye,
            hidden: (live && !row.pending) || !row.can.edit,
            // CMS-PUB-005: opens the signed 60-minute preview of the saved version.
            onSelect: () =>
                window.open(
                    preview.url({ type: 'page', id: row.id }),
                    '_blank',
                    'noopener',
                ),
        },
        {
            id: 'publish',
            label: row.pending ? 'Publish changes' : 'Publish',
            icon: Upload,
            hidden: (live && !row.pending) || !row.can.publish,
            onSelect: () =>
                router.post(
                    publish.url({ type: 'page', id: row.id }),
                    {},
                    { preserveScroll: true },
                ),
        },
        {
            id: 'unpublish',
            label: 'Unpublish',
            icon: EyeOff,
            hidden: !live || !row.can.publish,
            onSelect: () =>
                router.post(
                    unpublish.url({ type: 'page', id: row.id }),
                    {},
                    { preserveScroll: true },
                ),
        },
        {
            id: 'duplicate',
            label: 'Duplicate',
            icon: Copy,
            hidden: !row.can.edit,
            disabledReason: row.can.duplicate
                ? null
                : 'System pages cannot be duplicated: create a new page instead.',
            onSelect: () => router.post(duplicate.url(row.id)),
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !row.can.edit || (!row.can.delete && !row.delete_reason),
            disabledReason: row.delete_reason,
            onSelect: () => void deleteOne(row),
        },
    ];
}

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
                ? pagesIndex.url()
                : `${pagesIndex.url()}?filter[status]=${key}`,
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
    <Head title="Pages" />

    <ListPageHeader
        title="Pages"
        description="The home page, the other pages of the website and the legal pages. Each is built from blocks; drafts stay hidden until you publish them."
        :tabs="statusTabs"
        :bin-count="binCount"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'page' } })"
    >
        <template #actions>
            <Button v-if="canCreate" as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New page
                </Link>
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="pages"
        noun="page"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search title or URL"
        :row-label="(row: PageRow) => row.title"
        :row-actions="rowActions"
        clickable-rows
        empty-title="No pages yet"
        empty-description="Pages appear on the website once they are published. Create the first one."
        @row-click="openRow"
    >
        <template #cell-title="{ row, search }">
            <div class="flex min-w-0 items-center gap-3">
                <span class="min-w-0">
                    <span class="flex items-center gap-1.5 font-medium">
                        <span class="truncate"
                            ><HighlightText :text="row.title" :term="search"
                        /></span>
                        <Tooltip v-if="row.is_system">
                            <TooltipTrigger as-child>
                                <span
                                    class="inline-flex shrink-0 text-muted-foreground"
                                    tabindex="0"
                                >
                                    <Lock class="size-3.5" aria-hidden="true" />
                                    <span class="sr-only">System page</span>
                                </span>
                            </TooltipTrigger>
                            <TooltipContent class="max-w-64">
                                A system page: part of the website, so its
                                address is fixed and it cannot be deleted. You
                                can edit and publish it.
                            </TooltipContent>
                        </Tooltip>
                    </span>
                    <span
                        class="block truncate font-mono text-xs text-muted-foreground"
                        >{{ row.path }}</span
                    >
                </span>
            </div>
        </template>
        <template #cell-template="{ row }">{{
            row.template === 'legal' ? 'Legal' : 'Builder'
        }}</template>
        <template #cell-blocks_count="{ row }">
            <span class="tabular-nums">{{ row.blocks_count }}</span>
        </template>
        <template #cell-publish_status="{ row }">
            <!-- HELP-06: plain words plus the detail in a tooltip; colour is never the only signal. -->
            <span :title="statusHint(row)">
                <StatusBadge
                    :label="row.pending ? 'Pending changes' : row.status.label"
                    :tone="row.pending ? 'warning' : tone[row.status.value]"
                />
            </span>
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
