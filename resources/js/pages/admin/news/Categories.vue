<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowDownUp, ExternalLink, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import CategoryDeleteDialog from '@/components/admin/news/CategoryDeleteDialog.vue';
import CategoryDialog from '@/components/admin/news/CategoryDialog.vue';
import type { CategoryDraft } from '@/components/admin/news/CategoryDialog.vue';
import CategoryOrderDialog from '@/components/admin/news/CategoryOrderDialog.vue';
import NewsTabs from '@/components/admin/news/NewsTabs.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { destroy } from '@/routes/admin/news/categories';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterOption,
    PostCategoryRow,
    TableMeta,
    TableViewItem,
} from '@/types';

// ADM-SCR-08 News › Categories: a dialog entity with drag order. A category with posts cannot be deleted
// until its posts are moved to another category (DEL-03).
const props = defineProps<{
    rows: PostCategoryRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    binCount: number;
    all: Array<{ id: number; name: string }>;
}>();

const columns: DataTableColumn[] = [
    {
        id: 'name',
        label: 'Category',
        required: true,
        sortable: true,
        width: 280,
    },
    { id: 'slug', label: 'URL', width: 260 },
    {
        id: 'posts_count',
        label: 'Posts',
        sortable: true,
        width: 120,
        align: 'right',
    },
    {
        id: 'sort_order',
        label: 'Order',
        sortable: true,
        width: 100,
        hidden: true,
    },
];

const dialogOpen = ref(false);
const draft = ref<CategoryDraft | null>(null);
const orderOpen = ref(false);
const moveOpen = ref(false);
const moveRow = ref<PostCategoryRow | null>(null);

function openDialog(row: PostCategoryRow | null): void {
    draft.value = row
        ? {
              id: row.id,
              name: row.name,
              slug: row.slug,
              description: row.description ?? '',
          }
        : null;
    dialogOpen.value = true;
}

function openFromRow(row: PostCategoryRow): void {
    if (row.can.edit) {
        openDialog(row);
    }
}

const confirm = useConfirm();

async function deleteOne(row: PostCategoryRow): Promise<void> {
    if (row.posts_count > 0) {
        moveRow.value = row;
        moveOpen.value = true;

        return;
    }

    const answer = await confirm({
        title: 'Delete this category?',
        subject: row.name,
        description:
            'Its page leaves the website. The category moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete category',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

function rowActions(row: PostCategoryRow): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => openDialog(row),
        },
        {
            id: 'view',
            label: 'View on site',
            icon: ExternalLink,
            onSelect: () => window.open(row.path, '_blank', 'noopener'),
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

const others = computed(() =>
    props.all.filter((category) => category.id !== moveRow.value?.id),
);
</script>

<template>
    <Head title="News categories" />

    <ListPageHeader
        title="News"
        description="Categories group the articles. Each has its own page; the order below is the order of the category row on the news page."
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'post_category' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="orderOpen = true">
                <ArrowDownUp aria-hidden="true" />
                Order
            </Button>
            <Button @click="openDialog(null)">
                <Plus aria-hidden="true" />
                New category
            </Button>
        </template>
        <template #tabs>
            <NewsTabs current="categories" />
        </template>
    </ListPageHeader>

    <DataTable
        table-key="post-categories"
        noun="category"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search categories"
        :row-label="(row: PostCategoryRow) => row.name"
        :row-actions="rowActions"
        :clickable-rows="canManage"
        empty-title="No categories yet"
        empty-description="Every post needs a category, such as News or Buying guides. Create the first one."
        @row-click="openFromRow"
    >
        <template #cell-name="{ row, search }">
            <span class="min-w-0">
                <span class="block font-medium"
                    ><HighlightText :text="row.name" :term="search"
                /></span>
                <span
                    v-if="row.description"
                    class="block truncate text-xs text-muted-foreground"
                    >{{ row.description }}</span
                >
            </span>
        </template>
        <template #cell-slug="{ row }">
            <span class="font-mono text-xs text-muted-foreground">{{
                row.path
            }}</span>
        </template>
        <template #cell-posts_count="{ row }">
            <span class="tabular-nums">{{ row.posts_count }}</span>
        </template>
        <template #cell-sort_order>
            <span class="text-muted-foreground">–</span>
        </template>
    </DataTable>

    <CategoryDialog v-model:open="dialogOpen" :draft="draft" />
    <CategoryOrderDialog v-model:open="orderOpen" :items="all" />
    <CategoryDeleteDialog
        v-model:open="moveOpen"
        :category="moveRow"
        :others="others"
    />
</template>
