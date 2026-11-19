<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowDownUp, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import SocialOrderDialog from '@/components/admin/social/SocialOrderDialog.vue';
import { Button } from '@/components/ui/button';
import { useSocialList } from '@/composables/useSocialList';
import { formatPhone } from '@/lib/format';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import {
    bulk,
    create,
    destroy,
    edit,
    index,
    order as orderRoute,
} from '@/routes/admin/offices';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterOption,
    OfficeRow,
    OrderItem,
    TableMeta,
    TableViewItem,
} from '@/types';

// ADM-SCR-09 Offices: shown on the contact page, in the footer and in the structured data. An office
// with job posts cannot be deleted (DEL-03); the menu item is disabled and says why.
const props = defineProps<{
    rows: OfficeRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    binCount: number;
    order: OrderItem[];
}>();

const columns: DataTableColumn[] = [
    { id: 'name', label: 'Office', required: true, sortable: true, width: 240 },
    { id: 'address', label: 'Address', width: 340 },
    { id: 'phones', label: 'Phone', width: 200 },
    { id: 'city', label: 'City', sortable: true, width: 130 },
    {
        id: 'sort_order',
        label: 'Order',
        sortable: true,
        width: 90,
        hidden: true,
    },
];

const orderOpen = ref(false);

const { deleteOne, bulkActions } = useSocialList<OfficeRow>({
    routes: { index: index.url(), destroy, bulk },
    nouns: ['office', 'offices'],
    deleteEffect: 'It leaves the contact page and the footer at once.',
    label: (row) => row.name,
    canManage: () => props.canManage,
    filterOptions: () => props.filterOptions,
});

function openEditor(row: OfficeRow): void {
    router.visit(edit.url(row.id));
}

function rowActions(row: OfficeRow): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => openEditor(row),
        },
        {
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !row.can.delete,
            disabledReason: row.delete_block ?? undefined,
            onSelect: () => void deleteOne(row),
        },
    ];
}
</script>

<template>
    <Head title="Offices" />

    <ListPageHeader
        title="Offices"
        description="Addresses, hours and phone numbers shown on the contact page, in the footer and to search engines. The head office is listed first."
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'office' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="orderOpen = true">
                <ArrowDownUp aria-hidden="true" />
                Order
            </Button>
            <Button @click="router.visit(create.url())">
                <Plus aria-hidden="true" />
                New office
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="offices"
        noun="office"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search offices"
        :row-label="(row: OfficeRow) => row.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        :clickable-rows="canManage"
        empty-title="No offices yet"
        empty-description="Add your head office first: its address appears on the contact page and in the footer."
        @row-click="(row: OfficeRow) => row.can.edit && openEditor(row)"
    >
        <template #cell-name="{ row, search }">
            <span class="min-w-0">
                <span class="block font-medium"
                    ><HighlightText :text="row.name" :term="search"
                /></span>
                <span
                    v-if="row.is_head_office"
                    class="block text-xs text-muted-foreground"
                    >Head office</span
                >
            </span>
        </template>
        <template #cell-address="{ row, search }">
            <HighlightText :text="row.address" :term="search" />
        </template>
        <template #cell-phones="{ row }">
            <span class="tabular-nums">{{
                row.phones.map((phone) => formatPhone(phone)).join(', ')
            }}</span>
        </template>
        <template #cell-sort_order>
            <span class="text-muted-foreground">–</span>
        </template>
    </DataTable>

    <SocialOrderDialog
        v-model:open="orderOpen"
        title="Order offices"
        description="Drag a row, or use the arrows. The head office always comes first on the website; this is the order of the rest. Each move is saved at once."
        :items="order"
        :order-url="orderRoute.url()"
    />
</template>
