<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowDownUp, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import AmenityDialog from '@/components/admin/catalog/AmenityDialog.vue';
import AmenityOrderDialog from '@/components/admin/catalog/AmenityOrderDialog.vue';
import CatalogTabs from '@/components/admin/catalog/CatalogTabs.vue';
import UsageDialog from '@/components/admin/catalog/UsageDialog.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { amenityIcons } from '@/lib/amenity-icons';
import { bulk, destroy } from '@/routes/admin/amenities';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import type {
    AmenityDraft,
    AmenityRow,
    CatalogOption,
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    TableMeta,
    TableSelection,
    TableViewItem,
    UsageBlock,
} from '@/types';

// ADM-SCR-03 Amenities: a dialog entity with an icon picker. An amenity that projects list cannot
// be deleted (DEL-03): the dialog names the projects and offers to remove it from them first.
const props = defineProps<{
    rows: AmenityRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    binCount: number;
    icons: string[];
    categories: CatalogOption[];
    order: Array<{ id: number; name: string; icon: string; category: string }>;
}>();

const columns: DataTableColumn[] = [
    {
        id: 'name',
        label: 'Amenity',
        required: true,
        sortable: true,
        width: 280,
    },
    { id: 'category', label: 'Category', sortable: true, width: 160 },
    {
        id: 'projects_count',
        label: 'Used by',
        sortable: true,
        width: 140,
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

const filters = computed<FilterDef[]>(() => [
    {
        id: 'category',
        label: 'Category',
        kind: 'enum',
        options: props.filterOptions.category,
    },
]);

// --- dialogs -------------------------------------------------------------------------------------
const dialogOpen = ref(false);
const draft = ref<AmenityDraft | null>(null);
const orderOpen = ref(false);
const usageOpen = ref(false);
const usageRow = ref<AmenityRow | null>(null);

function openDialog(row: AmenityRow | null): void {
    draft.value = row
        ? {
              id: row.id,
              name: row.name,
              category: row.category.value,
              icon: row.icon,
          }
        : null;
    dialogOpen.value = true;
}

function openFromRow(row: AmenityRow): void {
    if (row.can.edit) {
        openDialog(row);
    }
}

// --- delete (DEL-02 / DEL-03) ---------------------------------------------------------------------
const confirm = useConfirm();

const usage = computed<UsageBlock | null>(() =>
    usageRow.value
        ? {
              message: `“${usageRow.value.name}” is used by ${usageRow.value.projects_count} ${usageRow.value.projects_count === 1 ? 'project' : 'projects'}. Remove it from them first, or let the system do it.`,
              total: usageRow.value.projects_count,
              items: usageRow.value.projects,
              list_label: 'Open the project',
          }
        : null,
);

async function deleteOne(row: AmenityRow): Promise<void> {
    if (row.projects_count > 0) {
        usageRow.value = row;
        usageOpen.value = true;

        return;
    }

    const answer = await confirm({
        title: 'Delete this amenity?',
        subject: row.name,
        description:
            'It moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete amenity',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(row.id), { preserveScroll: true });
    }
}

// The second confirm of DEL-03: detach from every project, then delete.
async function detachAndDelete(): Promise<void> {
    const row = usageRow.value;

    if (!row) {
        return;
    }

    const answer = await confirm({
        title: `Remove “${row.name}” from ${row.projects_count} ${row.projects_count === 1 ? 'project' : 'projects'} and delete it?`,
        description:
            'The projects stop listing it. Restoring the amenity from the Recycle bin does not add it back to them.',
        confirmLabel: 'Remove and delete',
        destructive: true,
    });

    if (answer) {
        usageOpen.value = false;
        router.delete(destroy.url(row.id), {
            data: { detach: true },
            preserveScroll: true,
        });
    }
}

async function deleteMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${selection.count === 1 ? 'amenity' : 'amenities'}?`,
        description:
            'Amenities that projects use are skipped and reported. The rest move to the Recycle bin.',
        confirmLabel: 'Delete amenities',
        destructive: true,
    });

    if (answer) {
        router.post(
            bulk.url(),
            selection.all
                ? { all: true, ...selection.query }
                : { ids: selection.ids },
            { preserveScroll: true },
        );
    }
}

function rowActions(row: AmenityRow): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            hidden: !row.can.edit,
            onSelect: () => openDialog(row),
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

const bulkActions = computed<DataTableBulkAction[]>(() =>
    props.canManage
        ? [
              {
                  id: 'delete',
                  label: 'Delete',
                  icon: Trash2,
                  destructive: true,
                  run: (selection) => void deleteMany(selection),
              },
          ]
        : [],
);
</script>

<template>
    <Head title="Amenities" />

    <ListPageHeader
        title="Amenities"
        description="The facilities a project can offer, with an icon each. They appear as icon rows on project pages."
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'amenity' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="orderOpen = true">
                <ArrowDownUp aria-hidden="true" />
                Order
            </Button>
            <Button @click="openDialog(null)">
                <Plus aria-hidden="true" />
                New amenity
            </Button>
        </template>
        <template #tabs>
            <CatalogTabs current="amenities" />
        </template>
    </ListPageHeader>

    <DataTable
        table-key="amenities"
        noun="amenity"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search amenities"
        :row-label="(row: AmenityRow) => row.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        :clickable-rows="canManage"
        empty-title="No amenities yet"
        empty-description="Amenities such as a gym, generator or rooftop garden appear on project pages once a project lists them. Create the first one."
        @row-click="openFromRow"
    >
        <template #cell-name="{ row, search }">
            <span class="flex items-center gap-3">
                <span
                    class="grid size-8 shrink-0 place-items-center rounded-md bg-muted text-muted-foreground"
                >
                    <component
                        :is="amenityIcons[row.icon]"
                        v-if="amenityIcons[row.icon]"
                        class="size-4"
                        aria-hidden="true"
                    />
                </span>
                <HighlightText :text="row.name" :term="search" />
            </span>
        </template>
        <template #cell-category="{ row }">{{ row.category.label }}</template>
        <template #cell-projects_count="{ row }">
            <span class="tabular-nums">{{ row.projects_count }}</span>
            <span class="ml-1 text-xs text-muted-foreground">{{
                row.projects_count === 1 ? 'project' : 'projects'
            }}</span>
        </template>
        <template #cell-sort_order="{ row }">
            <span class="text-muted-foreground">–</span>
        </template>
    </DataTable>

    <AmenityDialog
        v-model:open="dialogOpen"
        :draft="draft"
        :categories="categories"
        :icons="icons"
    />
    <AmenityOrderDialog v-model:open="orderOpen" :items="order" />
    <UsageDialog
        v-model:open="usageOpen"
        title="This amenity is in use"
        :usage="usage"
        :fix-label="canManage ? 'Remove from projects and delete…' : undefined"
        @fix="detachAndDelete"
    />
</template>
