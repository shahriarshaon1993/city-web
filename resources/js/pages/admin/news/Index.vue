<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
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
import { computed } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import NewsTabs from '@/components/admin/news/NewsTabs.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import {
    create,
    destroy,
    duplicate,
    index as newsIndex,
} from '@/routes/admin/news';
import { preview, publish, unpublish } from '@/routes/admin/publishing';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    PostRow,
    TableMeta,
    TableViewItem,
} from '@/types';

// ADM-SCR-08 News › Posts. Row verbs live in the ⋯ menu (DEC-75). A post with no category cannot be
// created, so the button says what to do first (HELP-08). Delete goes to the Recycle bin with Undo (DEL-02).
const props = defineProps<{
    rows: PostRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canCreate: boolean;
    canShareViews: boolean;
    hasCategories: boolean;
    binCount: number;
}>();

const columns: DataTableColumn[] = [
    { id: 'title', label: 'Post', required: true, sortable: true, width: 380 },
    { id: 'category', label: 'Category', width: 170 },
    { id: 'author', label: 'Author', width: 160, hidden: true },
    { id: 'publish_status', label: 'Publishing', sortable: true, width: 150 },
    { id: 'published_at', label: 'Published', sortable: true, width: 160 },
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
        id: 'category',
        label: 'Category',
        kind: 'enum',
        options: props.filterOptions.category,
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
function statusHint(row: PostRow): string | undefined {
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

function openRow(row: PostRow): void {
    if (row.can.edit) {
        router.visit(row.edit_url);
    }
}

const confirm = useConfirm();

async function deleteOne(row: PostRow): Promise<void> {
    const live = row.status.value === 'published';
    const answer = await confirm({
        title: 'Delete this post?',
        subject: row.title,
        description: live
            ? 'It leaves the website at once: its address shows “page not found”. The post moves to the Recycle bin and can be restored as a draft for 90 days.'
            : 'The post moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete post',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

function rowActions(row: PostRow): DataTableRowAction[] {
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
                    preview.url({ type: 'post', id: row.id }),
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
                    publish.url({ type: 'post', id: row.id }),
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
                    unpublish.url({ type: 'post', id: row.id }),
                    {},
                    { preserveScroll: true },
                ),
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
                ? newsIndex.url()
                : `${newsIndex.url()}?filter[status]=${key}`,
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
    <Head title="News" />

    <ListPageHeader
        title="News"
        description="Articles for the news page: construction updates, buying guides and announcements. Drafts stay hidden until you publish them."
        :tabs="statusTabs"
        :bin-count="binCount"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'post' } })"
    >
        <template #actions>
            <Button v-if="canCreate && hasCategories" as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New post
                </Link>
            </Button>
            <Button
                v-else-if="canCreate"
                disabled
                title="Create a category first"
            >
                <Plus aria-hidden="true" />
                New post
            </Button>
        </template>
        <template #tabs>
            <NewsTabs current="posts" />
        </template>
    </ListPageHeader>

    <p
        v-if="canCreate && !hasCategories"
        class="-mt-2 mb-4 text-sm text-muted-foreground"
    >
        A post needs a category. Add one under the Categories tab first.
    </p>

    <DataTable
        table-key="posts"
        noun="post"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search title, summary or URL"
        :row-label="(row: PostRow) => row.title"
        :row-actions="rowActions"
        clickable-rows
        empty-title="No posts yet"
        empty-description="Posts appear on the news page once they are published. Write the first one."
        @row-click="openRow"
    >
        <template #cell-title="{ row, search }">
            <span class="min-w-0">
                <span class="flex items-center gap-1.5 font-medium">
                    <span class="truncate"
                        ><HighlightText :text="row.title" :term="search"
                    /></span>
                    <span
                        v-if="row.is_featured"
                        class="inline-flex shrink-0 text-warning"
                        title="Featured on the news page"
                    >
                        <Star
                            class="size-3.5 fill-current"
                            aria-hidden="true"
                        />
                        <span class="sr-only">Featured</span>
                    </span>
                </span>
                <span
                    class="block truncate font-mono text-xs text-muted-foreground"
                    >{{ row.path }}</span
                >
            </span>
        </template>
        <template #cell-category="{ row }">{{ row.category }}</template>
        <template #cell-author="{ row }">{{ row.author ?? '—' }}</template>
        <template #cell-publish_status="{ row }">
            <!-- HELP-06: plain words plus the detail in a tooltip; colour is never the only signal. -->
            <span :title="statusHint(row)">
                <StatusBadge
                    :label="row.pending ? 'Pending changes' : row.status.label"
                    :tone="row.pending ? 'warning' : tone[row.status.value]"
                />
            </span>
        </template>
        <template #cell-published_at="{ row }">
            <span v-if="row.published_at" class="tabular-nums">{{
                formatDateTime(row.published_at).split(' · ')[0]
            }}</span>
            <span v-else class="text-muted-foreground">—</span>
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
