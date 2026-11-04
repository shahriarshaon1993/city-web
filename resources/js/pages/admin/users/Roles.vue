<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import UsersRolesTabs from '@/components/admin/users/UsersRolesTabs.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/admin-format';
import { create, destroy, edit } from '@/routes/admin/roles';
import type {
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    RoleRow,
    TableMeta,
    TableViewItem,
} from '@/types';

const props = defineProps<{
    rows: RoleRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    canManageUsers: boolean;
    canShareViews: boolean;
}>();

const columns: DataTableColumn[] = [
    { id: 'name', label: 'Role', required: true, sortable: true, width: 260 },
    { id: 'type', label: 'Type', width: 130 },
    {
        id: 'users_count',
        label: 'Users',
        sortable: true,
        width: 110,
        align: 'right',
    },
    {
        id: 'permissions_count',
        label: 'Permissions',
        sortable: true,
        width: 140,
        align: 'right',
    },
    {
        id: 'created_at',
        label: 'Created',
        sortable: true,
        hidden: true,
        width: 150,
    },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'type',
        label: 'Type',
        kind: 'enum',
        options: props.filterOptions.type,
    },
    { id: 'users', label: 'Users', kind: 'number' },
]);

const target = ref<RoleRow | null>(null);
const confirmOpen = ref(false);
const processing = ref(false);

function rowActions(role: RoleRow): DataTableRowAction[] {
    return [
        {
            id: 'open',
            label: role.type === 'system' ? 'View permissions' : 'Edit',
            icon: role.type === 'system' ? Eye : Pencil,
            onSelect: () => router.visit(edit.url(role.id)),
        },
        {
            id: 'delete',
            label: 'Delete role',
            icon: Trash2,
            destructive: true,
            disabledReason: role.can.delete ? null : role.blocked.delete,
            onSelect: () => {
                target.value = role;
                confirmOpen.value = true;
            },
        },
    ];
}

function openRole(role: RoleRow): void {
    router.visit(edit.url(role.id));
}

function confirmed(): void {
    if (!target.value) {
        return;
    }

    processing.value = true;
    router.delete(destroy.url(target.value.id), {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmOpen.value = false;
        },
    });
}
</script>

<template>
    <Head title="Roles" />

    <AdminPageHeader
        title="Users & roles"
        description="The six system roles are fixed. Create a custom role, for example “HR”, to give someone exactly the permissions they need."
    >
        <template #actions>
            <Button as-child>
                <Link :href="create.url()">
                    <Plus aria-hidden="true" />
                    New role
                </Link>
            </Button>
        </template>
        <template #tabs>
            <UsersRolesTabs
                current="roles"
                :can-users="canManageUsers"
                can-roles
            />
        </template>
    </AdminPageHeader>

    <DataTable
        table-key="roles"
        noun="role"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search roles"
        :row-label="(role: RoleRow) => role.name"
        :row-actions="rowActions"
        clickable-rows
        @row-click="openRole"
    >
        <template #cell-name="{ row, search }">
            <Link :href="edit.url(row.id)" class="font-medium hover:underline">
                <HighlightText :text="row.name" :term="search" />
            </Link>
        </template>
        <template #cell-type="{ row }">
            <StatusBadge
                :label="row.type === 'system' ? 'System' : 'Custom'"
                :tone="row.type === 'system' ? 'neutral' : 'info'"
            />
        </template>
        <template #cell-created_at="{ row }">
            <span class="tabular-nums">{{
                row.created_at
                    ? formatDateTime(row.created_at).split(' · ')[0]
                    : '–'
            }}</span>
        </template>
    </DataTable>

    <ConfirmDialog
        v-model:open="confirmOpen"
        :title="`Delete the role “${target?.name ?? ''}”?`"
        description="The role and its permission set are removed for good. Nobody holds this role, so no one loses access."
        confirm-label="Delete role"
        destructive
        :processing="processing"
        @confirm="confirmed"
    />
</template>
