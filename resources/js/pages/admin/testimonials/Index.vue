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
} from '@/routes/admin/testimonials';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    OrderItem,
    TableMeta,
    TableViewItem,
    TestimonialRow,
} from '@/types';

// ADM-SCR-09 Testimonials: quotes in the order the site shows them. The editor is a full page (7
// fields, a photo and a project); the Published switch and the order save at once.
const props = defineProps<{
    rows: TestimonialRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    binCount: number;
    order: OrderItem[];
}>();

const columns: DataTableColumn[] = [
    {
        id: 'person_name',
        label: 'Person',
        required: true,
        sortable: true,
        width: 260,
    },
    { id: 'quote', label: 'Quote', width: 380 },
    { id: 'project', label: 'Project', width: 200 },
    { id: 'is_nrb', label: 'NRB', width: 90 },
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
        id: 'nrb',
        label: 'NRB',
        kind: 'enum',
        options: props.filterOptions.nrb ?? [],
    },
]);

const orderOpen = ref(false);

const { isPublished, togglePublished, deleteOne, bulkActions, statusTabs } =
    useSocialList<TestimonialRow>({
        routes: { index: index.url(), published, destroy, bulk },
        nouns: ['testimonial', 'testimonials'],
        deleteEffect: 'It leaves the website at once.',
        label: (row) => row.person_name,
        canManage: () => props.canManage,
        filterOptions: () => props.filterOptions,
    });

function openEditor(row: TestimonialRow): void {
    router.visit(edit.url(row.id));
}

function rowActions(row: TestimonialRow): DataTableRowAction[] {
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
    <Head title="Testimonials" />

    <ListPageHeader
        title="Testimonials"
        description="Customer quotes shown on the home page and wherever a testimonials block is placed. Hidden ones stay here but leave the website."
        :tabs="statusTabs"
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'testimonial' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="orderOpen = true">
                <ArrowDownUp aria-hidden="true" />
                Order
            </Button>
            <Button @click="router.visit(create.url())">
                <Plus aria-hidden="true" />
                New testimonial
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="testimonials"
        noun="testimonial"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search name or quote"
        :row-label="(row: TestimonialRow) => row.person_name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        :clickable-rows="canManage"
        empty-title="No testimonials yet"
        empty-description="Quotes from buyers and owners appear in the testimonials block on the home page once published. Add the first one."
        @row-click="(row: TestimonialRow) => row.can.edit && openEditor(row)"
    >
        <template #cell-person_name="{ row, search }">
            <span class="flex items-center gap-3">
                <img
                    v-if="row.thumb"
                    :src="row.thumb"
                    alt=""
                    class="size-9 shrink-0 rounded-full object-cover"
                />
                <span
                    v-else
                    class="grid size-9 shrink-0 place-items-center rounded-full bg-muted text-xs font-medium text-muted-foreground"
                    aria-hidden="true"
                    >{{ row.person_name.slice(0, 1) }}</span
                >
                <span class="min-w-0">
                    <span class="block font-medium"
                        ><HighlightText :text="row.person_name" :term="search"
                    /></span>
                    <span
                        v-if="row.designation"
                        class="block truncate text-xs text-muted-foreground"
                        >{{ row.designation }}</span
                    >
                </span>
            </span>
        </template>
        <template #cell-quote="{ row, search }">
            <span class="line-clamp-2 text-sm"
                ><HighlightText :text="row.quote" :term="search"
            /></span>
        </template>
        <template #cell-project="{ row }">
            <span v-if="row.project">{{ row.project.name }}</span>
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-is_nrb="{ row }">{{
            row.is_nrb ? 'Yes' : '–'
        }}</template>
        <template #cell-is_published="{ row }">
            <Switch
                :model-value="isPublished(row)"
                :disabled="!row.can.edit"
                :aria-label="`${row.person_name} is ${isPublished(row) ? 'published' : 'hidden'}`"
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
        title="Order testimonials"
        description="Drag a row, or use the arrows. This is the order on the website. Each move is saved at once."
        :items="order"
        :order-url="orderRoute.url()"
    />
</template>
