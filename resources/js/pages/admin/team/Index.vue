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
} from '@/routes/admin/team';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    OrderItem,
    SocialChoice,
    TableMeta,
    TableViewItem,
    TeamMemberRow,
} from '@/types';

// ADM-SCR-09 Team: people on the team grids and the leadership page, in drag order inside each group.
const props = defineProps<{
    rows: TeamMemberRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManage: boolean;
    canShareViews: boolean;
    binCount: number;
    order: OrderItem[];
    groups: SocialChoice[];
}>();

const columns: DataTableColumn[] = [
    { id: 'name', label: 'Person', required: true, sortable: true, width: 280 },
    { id: 'group', label: 'Group', sortable: true, width: 150 },
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
        id: 'group',
        label: 'Group',
        kind: 'enum',
        options: props.filterOptions.group ?? [],
    },
]);

const orderOpen = ref(false);

const { isPublished, togglePublished, deleteOne, bulkActions, statusTabs } =
    useSocialList<TeamMemberRow>({
        routes: { index: index.url(), published, destroy, bulk },
        nouns: ['team member', 'team members'],
        deleteEffect: 'They leave the team pages at once.',
        label: (row) => row.name,
        canManage: () => props.canManage,
        filterOptions: () => props.filterOptions,
    });

function openEditor(row: TeamMemberRow): void {
    router.visit(edit.url(row.id));
}

function rowActions(row: TeamMemberRow): DataTableRowAction[] {
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
    <Head title="Team" />

    <ListPageHeader
        title="Team"
        description="Leadership, management and team profiles for the team grids and the leadership page. Hidden people stay here but leave the website."
        :tabs="statusTabs"
        :bin-count="canManage ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'team_member' } })"
    >
        <template v-if="canManage" #actions>
            <Button variant="outline" @click="orderOpen = true">
                <ArrowDownUp aria-hidden="true" />
                Order
            </Button>
            <Button @click="router.visit(create.url())">
                <Plus aria-hidden="true" />
                New team member
            </Button>
        </template>
    </ListPageHeader>

    <DataTable
        table-key="team"
        noun="team member"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search name or role"
        :row-label="(row: TeamMemberRow) => row.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        :selectable="canManage"
        :clickable-rows="canManage"
        empty-title="No team members yet"
        empty-description="People you add here appear on the leadership page and in team grids once published. Add the first one."
        @row-click="(row: TeamMemberRow) => row.can.edit && openEditor(row)"
    >
        <template #cell-name="{ row, search }">
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
                    >{{ row.name.slice(0, 1) }}</span
                >
                <span class="min-w-0">
                    <span class="block font-medium"
                        ><HighlightText :text="row.name" :term="search"
                    /></span>
                    <span class="block truncate text-xs text-muted-foreground"
                        ><HighlightText :text="row.role_title" :term="search"
                    /></span>
                </span>
            </span>
        </template>
        <template #cell-group="{ row }">{{ row.group.label }}</template>
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

    <SocialOrderDialog
        v-model:open="orderOpen"
        title="Order team members"
        description="Pick a group, then drag a row or use the arrows. This is the order on the website inside that group. Each move is saved at once."
        :items="order"
        :groups="groups"
        :order-url="orderRoute.url()"
    />
</template>
