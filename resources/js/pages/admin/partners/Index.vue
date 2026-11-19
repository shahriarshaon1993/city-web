<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowDownUp, Eye, EyeOff, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, provide, ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import {
    assetMapKey,
    createAssetMap,
} from '@/components/admin/catalog/context';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import PartnerDialog from '@/components/admin/social/PartnerDialog.vue';
import SocialOrderDialog from '@/components/admin/social/SocialOrderDialog.vue';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { useSocialList } from '@/composables/useSocialList';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import {
    bulk,
    destroy,
    index,
    order as orderRoute,
    published,
} from '@/routes/admin/partners';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    OrderItem,
    PartnerRow,
    SocialChoice,
    TableMeta,
    TableViewItem,
} from '@/types';

// ADM-SCR-09 Partners: logos in marquee order. Small form, so it is a dialog (owner rule: only small
// forms in dialogs); the Published switch and the order save at once.
const props = defineProps<{
    rows: PartnerRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    binCount: number;
    order: OrderItem[];
    categories: SocialChoice[];
}>();

// The logo field previews from the asset map; every row already carries its logo.
provide(assetMapKey, createAssetMap({}));

const columns: DataTableColumn[] = [
    {
        id: 'name',
        label: 'Partner',
        required: true,
        sortable: true,
        width: 300,
    },
    { id: 'category', label: 'Category', sortable: true, width: 160 },
    { id: 'url', label: 'Website', width: 260 },
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
        label: 'Category',
        kind: 'enum',
        options: props.filterOptions.category ?? [],
    },
]);

const dialogOpen = ref(false);
const editing = ref<PartnerRow | null>(null);
const orderOpen = ref(false);

function openDialog(row: PartnerRow | null): void {
    editing.value = row;
    dialogOpen.value = true;
}

const { isPublished, togglePublished, deleteOne, bulkActions, statusTabs } =
    useSocialList<PartnerRow>({
        routes: { index: index.url(), published, destroy, bulk },
        nouns: ['partner', 'partners'],
        deleteEffect: 'Its logo leaves the website at once.',
        label: (row) => row.name,
        canManage: () => props.canManage,
        filterOptions: () => props.filterOptions,
    });

function rowActions(row: PartnerRow): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => openDialog(row),
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
    <Head title="Partners" />

    <ListPageHeader
        title="Partners"
        description="Banks, suppliers and consultants whose logos run in the partners strip. Hidden ones stay here but leave the website."
        :tabs="statusTabs"
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'partner' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="orderOpen = true">
                <ArrowDownUp aria-hidden="true" />
                Order
            </Button>
            <Button @click="openDialog(null)">
                <Plus aria-hidden="true" />
                New partner
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="partners"
        noun="partner"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search partners"
        :row-label="(row: PartnerRow) => row.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        :clickable-rows="canManage"
        empty-title="No partners yet"
        empty-description="Logos you add here run in the partners strip once published. Add the first one."
        @row-click="(row: PartnerRow) => row.can.edit && openDialog(row)"
    >
        <template #cell-name="{ row, search }">
            <span class="flex items-center gap-3">
                <span
                    class="grid h-9 w-14 shrink-0 place-items-center overflow-hidden rounded-md bg-muted"
                >
                    <img
                        v-if="row.logo?.thumb_url"
                        :src="row.logo.thumb_url"
                        alt=""
                        class="max-h-7 max-w-12 object-contain"
                    />
                </span>
                <HighlightText :text="row.name" :term="search" />
            </span>
        </template>
        <template #cell-category="{ row }">{{ row.category.label }}</template>
        <template #cell-url="{ row }">
            <span
                v-if="row.url"
                class="font-mono text-xs text-muted-foreground"
                >{{ row.url }}</span
            >
            <span v-else class="text-muted-foreground">–</span>
        </template>
        <template #cell-is_published="{ row }">
            <Switch
                :model-value="isPublished(row)"
                :disabled="!row.can.edit"
                :aria-label="`${row.name} is ${isPublished(row) ? 'published' : 'hidden'}`"
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

    <PartnerDialog
        v-model:open="dialogOpen"
        :partner="editing"
        :categories="categories"
    />
    <SocialOrderDialog
        v-model:open="orderOpen"
        title="Order partners"
        description="Drag a row, or use the arrows. This is the order of the logos in the strip. Each move is saved at once."
        :items="order"
        :order-url="orderRoute.url()"
    />
</template>
