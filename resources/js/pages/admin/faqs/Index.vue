<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowDownUp, Eye, EyeOff, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import SocialOrderDialog from '@/components/admin/social/SocialOrderDialog.vue';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { useSocialList } from '@/composables/useSocialList';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import {
    bulk,
    create,
    destroy,
    edit,
    index,
    order as orderRoute,
    published,
} from '@/routes/admin/faqs';
import type {
    DataTableColumn,
    DataTableRowAction,
    FaqRow,
    FilterDef,
    FilterOption,
    OrderItem,
    SocialChoice,
    TableMeta,
    TableViewItem,
} from '@/types';

// ADM-SCR-09 FAQs: questions grouped by topic on the FAQ page. The editor is a full page (a rich-text
// answer and where the question shows); the order is set inside one topic at a time.
const props = defineProps<{
    rows: FaqRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    binCount: number;
    order: OrderItem[];
    categories: SocialChoice[];
}>();

const columns: DataTableColumn[] = [
    {
        id: 'question',
        label: 'Question',
        required: true,
        sortable: true,
        width: 420,
    },
    { id: 'category', label: 'Topic', sortable: true, width: 170 },
    { id: 'show_on', label: 'Shown on', width: 240 },
    { id: 'is_published', label: 'Published', sortable: true, width: 120 },
    {
        id: 'sort_order',
        label: 'Order',
        sortable: true,
        width: 90,
        hidden: true,
    },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'category',
        label: 'Topic',
        kind: 'enum',
        options: props.filterOptions.category ?? [],
    },
]);

const orderOpen = ref(false);

const { isPublished, togglePublished, deleteOne, bulkActions, statusTabs } =
    useSocialList<FaqRow>({
        routes: { index: index.url(), published, destroy, bulk },
        nouns: ['FAQ', 'FAQs'],
        deleteEffect: 'It leaves the website at once.',
        label: (row) => row.question,
        canManage: () => props.canManage,
        filterOptions: () => props.filterOptions,
    });

function openEditor(row: FaqRow): void {
    router.visit(edit.url(row.id));
}

function rowActions(row: FaqRow): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => openEditor(row),
        },
        {
            id: 'toggle',
            label: row.is_published ? 'Unpublish' : 'Publish',
            icon: row.is_published ? EyeOff : Eye,
            hidden: !row.can.edit,
            onSelect: () => togglePublished(row, !row.is_published),
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
</script>

<template>
    <Head title="FAQs" />

    <ListPageHeader
        title="FAQs"
        description="Questions and answers for the FAQ page, project pages, area pages and FAQ blocks. Hidden ones stay here but leave the website."
        :tabs="statusTabs"
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'faq' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="orderOpen = true">
                <ArrowDownUp aria-hidden="true" />
                Order
            </Button>
            <Button @click="router.visit(create.url())">
                <Plus aria-hidden="true" />
                New question
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="faqs"
        noun="question"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search questions and answers"
        :row-label="(row: FaqRow) => row.question"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        :clickable-rows="canManage"
        empty-title="No questions yet"
        empty-description="Questions appear on the FAQ page, and on project and area pages they are attached to, once published. Add the first one."
        @row-click="(row: FaqRow) => row.can.edit && openEditor(row)"
    >
        <template #cell-question="{ row, search }">
            <span class="min-w-0">
                <span class="block font-medium"
                    ><HighlightText :text="row.question" :term="search"
                /></span>
                <span class="block truncate text-xs text-muted-foreground">{{
                    row.answer_text
                }}</span>
            </span>
        </template>
        <template #cell-category="{ row }">{{ row.category.label }}</template>
        <template #cell-show_on="{ row }">
            <span v-if="row.show_on.length || row.attached" class="text-sm">
                {{ row.show_on.join(', ') }}
                <span v-if="row.attached" class="text-muted-foreground"
                    >{{ row.show_on.length ? ' · ' : '' }}{{ row.attached }}
                    {{ row.attached === 1 ? 'page' : 'pages' }} attached</span
                >
            </span>
            <span v-else class="text-muted-foreground">Blocks only</span>
        </template>
        <template #cell-is_published="{ row }">
            <Switch
                :model-value="isPublished(row)"
                :disabled="!row.can.edit"
                :aria-label="`${row.question} is ${isPublished(row) ? 'published' : 'hidden'}`"
                @click.stop
                @update:model-value="
                    (value: boolean) => togglePublished(row, value)
                "
            />
        </template>
        <template #cell-sort_order>
            <span class="text-muted-foreground">–</span>
        </template>
    </DataTable>

    <SocialOrderDialog
        v-model:open="orderOpen"
        title="Order questions"
        description="Pick a topic, then drag a row or use the arrows. This is the order on the FAQ page inside that topic. Each move is saved at once."
        :items="order"
        :groups="categories"
        :order-url="orderRoute.url()"
    />
</template>
