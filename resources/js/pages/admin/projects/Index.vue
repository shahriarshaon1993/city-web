<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowDownUp,
    Copy,
    ExternalLink,
    Eye,
    EyeOff,
    Pencil,
    Plus,
    Star,
    Trash2,
    Upload,
} from '@lucide/vue';
import { computed, reactive } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { index as amenitiesIndex } from '@/routes/admin/amenities';
import {
    bulk,
    create,
    destroy,
    duplicate,
    featured,
    publish,
    unpublish,
    index as projectsIndex,
} from '@/routes/admin/projects';
import { index as featuredIndex } from '@/routes/admin/projects/featured';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    ProjectRow,
    RedirectOption,
    TableMeta,
    TableSelection,
    TableViewItem,
} from '@/types';

// ADM-SCR-01 Projects: the list of every development. Row verbs live in the ⋯ menu (DEC-75); the
// featured star is inline (CRUD-01); Delete goes to the Recycle bin with Undo and, for a published
// project, an optional redirect (DEL-02). The "Leads (30d)" column arrives with the Leads module.
const props = defineProps<{
    rows: ProjectRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canCreate: boolean;
    canShareViews: boolean;
    binCount: number;
    redirectOptions: RedirectOption[];
}>();

const columns: DataTableColumn[] = [
    {
        id: 'name',
        label: 'Project',
        required: true,
        sortable: true,
        width: 320,
    },
    { id: 'segment', label: 'Segment', sortable: true, width: 130 },
    { id: 'lifecycle', label: 'Project status', sortable: true, width: 150 },
    {
        id: 'booking_state',
        label: 'Booking',
        sortable: true,
        width: 130,
        hidden: true,
    },
    { id: 'area', label: 'Area', width: 170 },
    { id: 'publish_status', label: 'Publishing', sortable: true, width: 140 },
    { id: 'is_featured', label: 'Featured', sortable: true, width: 100 },
    { id: 'updated_at', label: 'Updated', sortable: true, width: 190 },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'segment',
        label: 'Segment',
        kind: 'enum',
        options: props.filterOptions.segment,
    },
    {
        id: 'lifecycle',
        label: 'Project status',
        kind: 'enum',
        options: props.filterOptions.lifecycle,
    },
    {
        id: 'booking',
        label: 'Booking',
        kind: 'enum',
        options: props.filterOptions.booking,
    },
    {
        id: 'status',
        label: 'Publishing',
        kind: 'enum',
        options: props.filterOptions.status,
    },
    {
        id: 'area',
        label: 'Area',
        kind: 'enum',
        options: props.filterOptions.area,
    },
    {
        id: 'featured',
        label: 'Featured',
        kind: 'enum',
        options: props.filterOptions.featured,
    },
    { id: 'updated', label: 'Updated', kind: 'date' },
]);

const tone = {
    published: 'success',
    scheduled: 'info',
    archived: 'warning',
    draft: 'neutral',
} as const;

// --- inline featured star (CRUD-01): optimistic, the server's answer wins ------------------------
const pending = reactive<Record<number, boolean>>({});

function isFeatured(row: ProjectRow): boolean {
    return pending[row.id] ?? row.is_featured;
}

function toggleFeatured(row: ProjectRow): void {
    const value = !isFeatured(row);
    pending[row.id] = value;
    router.patch(
        featured.url(row.id),
        { featured: value },
        {
            preserveScroll: true,
            onFinish: () => {
                delete pending[row.id];
            },
        },
    );
}

// --- verbs --------------------------------------------------------------------------------------
function openRow(row: ProjectRow): void {
    if (row.can.edit) {
        router.visit(row.edit_url);
    }
}

const confirm = useConfirm();
const noun = (count: number): string => (count === 1 ? 'project' : 'projects');

async function deleteOne(row: ProjectRow): Promise<void> {
    const live = row.status.value === 'published';
    const answer = await confirm({
        title: 'Delete this project?',
        subject: row.name,
        description: live
            ? 'It leaves the website. Visitors get “Gone” for 90 days unless you send them to another page. The project moves to the Recycle bin and can be restored as a draft.'
            : 'The project moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete project',
        destructive: true,
        redirect: live
            ? {
                  label: 'Redirect visitors to…',
                  options: props.redirectOptions.filter(
                      (option) => option.value !== `/projects/${row.slug}`,
                  ),
              }
            : undefined,
    });

    if (answer) {
        router.delete(destroy.url(row.id), {
            data: { redirect_to: answer.redirect },
            preserveScroll: true,
        });
    }
}

async function deleteMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${noun(selection.count)}?`,
        description:
            'Published projects leave the website and visitors get “Gone” for 90 days. The projects move to the Recycle bin and can be restored as drafts.',
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
    action: 'publish' | 'unpublish' | 'delete',
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

function rowActions(row: ProjectRow): DataTableRowAction[] {
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
            label: 'Preview',
            icon: Eye,
            hidden: live,
            disabledReason:
                'Preview of drafts arrives with the publishing workflow.',
            onSelect: () => undefined,
        },
        {
            id: 'publish',
            label: 'Publish',
            icon: Upload,
            hidden: live || !row.can.publish,
            onSelect: () =>
                router.post(publish.url(row.id), {}, { preserveScroll: true }),
        },
        {
            id: 'unpublish',
            label: 'Unpublish',
            icon: EyeOff,
            hidden: !live || !row.can.publish,
            onSelect: () =>
                router.post(
                    unpublish.url(row.id),
                    {},
                    { preserveScroll: true },
                ),
        },
        {
            id: 'feature',
            label: isFeatured(row)
                ? 'Remove from featured'
                : 'Feature on home page',
            icon: Star,
            hidden: !row.can.edit,
            onSelect: () => toggleFeatured(row),
        },
        {
            id: 'duplicate',
            label: 'Duplicate',
            icon: Copy,
            hidden: !row.can.create,
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

const bulkActions = computed<DataTableBulkAction[]>(() => {
    const actions: DataTableBulkAction[] = [];

    if (props.rows.some((row) => row.can.publish)) {
        actions.push(
            {
                id: 'publish',
                label: 'Publish',
                icon: Upload,
                run: (selection) => postBulk('publish', selection),
            },
            {
                id: 'unpublish',
                label: 'Unpublish',
                icon: EyeOff,
                run: (selection) => postBulk('unpublish', selection),
            },
        );
    }

    if (props.rows.some((row) => row.can.delete)) {
        actions.push({
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            run: (selection) => void deleteMany(selection),
        });
    }

    return actions;
});

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
                ? projectsIndex.url()
                : `${projectsIndex.url()}?filter[status]=${key}`,
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
    <Head title="Projects" />

    <ListPageHeader
        title="Projects"
        description="Every development on the website. Drafts stay hidden until you publish them."
        :tabs="statusTabs"
        :bin-count="binCount"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'project' } })"
    >
        <template #actions>
            <Button as-child variant="ghost">
                <Link :href="amenitiesIndex.url()">Amenities</Link>
            </Button>
            <Button as-child variant="outline">
                <Link :href="featuredIndex.url()">
                    <ArrowDownUp aria-hidden="true" />
                    Featured order
                </Link>
            </Button>
            <Button v-if="canCreate" as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New project
                </Link>
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="projects"
        noun="project"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search name, URL or tagline"
        :row-label="(row: ProjectRow) => row.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="bulkActions.length > 0"
        clickable-rows
        empty-title="No projects yet"
        empty-description="Projects appear on the Projects page and the home page once they are published. Create the first one."
        @row-click="openRow"
    >
        <template #cell-name="{ row, search }">
            <div class="flex min-w-0 items-center gap-3">
                <span
                    class="h-9 w-12 shrink-0 overflow-hidden rounded bg-muted"
                >
                    <img
                        v-if="row.thumb"
                        :src="row.thumb"
                        alt=""
                        class="size-full object-cover"
                        loading="lazy"
                    />
                </span>
                <span class="min-w-0">
                    <span class="block truncate font-medium">
                        <HighlightText :text="row.name" :term="search" />
                    </span>
                    <span
                        class="block truncate font-mono text-xs text-muted-foreground"
                        >/{{ row.slug }}</span
                    >
                </span>
            </div>
        </template>
        <template #cell-segment="{ row }">{{ row.segment.label }}</template>
        <template #cell-lifecycle="{ row }">{{ row.lifecycle.label }}</template>
        <template #cell-booking_state="{ row }">{{
            row.booking ?? '–'
        }}</template>
        <template #cell-area="{ row }">
            <span v-if="row.area">
                {{ row.area }}
                <span
                    v-if="row.city"
                    class="ml-1 text-xs text-muted-foreground"
                    >{{ row.city }}</span
                >
            </span>
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-publish_status="{ row }">
            <StatusBadge
                :label="row.status.label"
                :tone="tone[row.status.value]"
            />
        </template>
        <template #cell-is_featured="{ row }">
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                :disabled="!row.can.edit"
                :aria-pressed="isFeatured(row)"
                :aria-label="`${row.name} is ${isFeatured(row) ? '' : 'not '}featured. Press to ${isFeatured(row) ? 'remove it from' : 'add it to'} the home page.`"
                @click.stop="toggleFeatured(row)"
            >
                <Star
                    :class="
                        isFeatured(row)
                            ? 'fill-warning text-warning'
                            : 'text-muted-foreground'
                    "
                    aria-hidden="true"
                />
            </Button>
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
