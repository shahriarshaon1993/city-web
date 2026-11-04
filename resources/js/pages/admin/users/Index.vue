<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Mail, Pencil, UserCheck, UserPlus, UserX } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import PersonCell from '@/components/admin/PersonCell.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import UserDialog from '@/components/admin/users/UserDialog.vue';
import UsersRolesTabs from '@/components/admin/users/UsersRolesTabs.vue';
import { Button } from '@/components/ui/button';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { activate, bulk, deactivate, resend } from '@/routes/admin/users';
import type {
    DataTableBulkAction,
    DataTableColumn,
    DataTableRowAction,
    FilterDef,
    FilterOption,
    TableMeta,
    TableSelection,
    TableViewItem,
    UserRow,
} from '@/types';

const props = defineProps<{
    rows: UserRow[];
    meta: TableMeta;
    views: TableViewItem[];
    filterOptions: Record<string, FilterOption[]>;
    assignableRoles: string[];
    canManageRoles: boolean;
    canShareViews: boolean;
}>();

// ADM-SCR-16 / design/admin.md §12.3 defaults for people tables.
const columns: DataTableColumn[] = [
    { id: 'name', label: 'User', required: true, sortable: true, width: 300 },
    { id: 'role', label: 'Role', width: 170 },
    {
        id: 'status',
        label: 'Status',
        sortable: true,
        sortKey: 'is_active',
        width: 130,
    },
    { id: 'last_login_at', label: 'Last sign-in', sortable: true, width: 250 },
    { id: 'created_at', label: 'Added', sortable: true, width: 140 },
    { id: 'email', label: 'Email', hidden: true, width: 220 },
    { id: 'phone', label: 'Phone', hidden: true, width: 150 },
];

const filters = computed<FilterDef[]>(() => [
    {
        id: 'role',
        label: 'Role',
        kind: 'enum',
        options: props.filterOptions.role,
    },
    {
        id: 'status',
        label: 'Status',
        kind: 'enum',
        options: props.filterOptions.status,
    },
    { id: 'last_login', label: 'Last sign-in', kind: 'date' },
    { id: 'created', label: 'Added', kind: 'date' },
]);

const statusLabel = {
    active: 'Active',
    invited: 'Invited',
    inactive: 'Inactive',
} as const;
const statusTone = {
    active: 'success',
    invited: 'info',
    inactive: 'neutral',
} as const;

// --- invite / edit dialog --------------------------------------------------------------------
const dialogOpen = ref(false);
const dialogUser = ref<UserRow | null>(null);

function openDialog(user: UserRow | null): void {
    dialogUser.value = user;
    dialogOpen.value = true;
}

function openFromRow(user: UserRow): void {
    if (user.can.edit) {
        openDialog(user);
    }
}

// --- confirm (CMP-ADM-CONFIRM) -------------------------------------------------------------------
type Pending =
    | { kind: 'deactivate-one'; user: UserRow }
    | { kind: 'deactivate-many'; selection: TableSelection };

const pending = ref<Pending | null>(null);
const confirmOpen = ref(false);
const processing = ref(false);

function ask(next: Pending): void {
    pending.value = next;
    confirmOpen.value = true;
}

const confirmTitle = computed(() => {
    if (pending.value?.kind === 'deactivate-one') {
        return `Deactivate ${pending.value.user.name}?`;
    }

    const count =
        pending.value?.kind === 'deactivate-many'
            ? pending.value.selection.count
            : 0;

    return `Deactivate ${count} ${count === 1 ? 'user' : 'users'}?`;
});

const confirmLabel = computed(() =>
    pending.value?.kind === 'deactivate-one'
        ? 'Deactivate user'
        : `Deactivate ${pending.value?.kind === 'deactivate-many' && pending.value.selection.count === 1 ? 'user' : 'users'}`,
);

const confirmDescription = computed(() =>
    pending.value?.kind === 'deactivate-one'
        ? 'They can no longer sign in, and any session they have open ends now. Their open leads are reassigned. You can activate them again at any time.'
        : 'They can no longer sign in, and any session they have open ends now. People you may not deactivate (yourself, or the last Super Admin) are skipped and listed. Open leads are reassigned. You can activate them again at any time.',
);

function confirmed(): void {
    const current = pending.value;

    if (!current) {
        return;
    }

    processing.value = true;

    const done = {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            confirmOpen.value = false;
        },
    };

    if (current.kind === 'deactivate-one') {
        router.post(deactivate.url(current.user.id), {}, done);
    } else {
        postBulk('deactivate', current.selection, done);
    }
}

function postBulk(
    action: 'activate' | 'deactivate',
    selection: TableSelection,
    options: Record<string, unknown> = { preserveScroll: true },
): void {
    router.post(
        bulk.url(),
        {
            action,
            ...(selection.all
                ? { all: true, ...selection.query }
                : { ids: selection.ids }),
        },
        options,
    );
}

// --- actions ----------------------------------------------------------------------------------
function rowActions(user: UserRow): DataTableRowAction[] {
    return [
        {
            id: 'edit',
            label: 'Edit',
            icon: Pencil,
            disabledReason: user.can.edit ? null : user.blocked.edit,
            onSelect: () => openDialog(user),
        },
        user.is_active
            ? {
                  id: 'deactivate',
                  label: 'Deactivate',
                  icon: UserX,
                  destructive: true,
                  disabledReason: user.can.toggle ? null : user.blocked.toggle,
                  onSelect: () => ask({ kind: 'deactivate-one', user }),
              }
            : {
                  id: 'activate',
                  label: 'Activate',
                  icon: UserCheck,
                  disabledReason: user.can.toggle ? null : user.blocked.toggle,
                  onSelect: () =>
                      router.post(
                          activate.url(user.id),
                          {},
                          { preserveScroll: true },
                      ),
              },
        {
            id: 'resend',
            label: 'Resend invitation',
            icon: Mail,
            hidden: !user.can.resend,
            onSelect: () =>
                router.post(resend.url(user.id), {}, { preserveScroll: true }),
        },
    ];
}

const bulkActions: DataTableBulkAction[] = [
    {
        id: 'activate',
        label: 'Activate',
        icon: UserCheck,
        run: (selection) => postBulk('activate', selection),
    },
    {
        id: 'deactivate',
        label: 'Deactivate',
        icon: UserX,
        destructive: true,
        run: (selection) => ask({ kind: 'deactivate-many', selection }),
    },
];
</script>

<template>
    <Head title="Users" />

    <AdminPageHeader
        title="Users & roles"
        description="Invite people, set their role and deactivate accounts. A role decides which sections someone can open."
    >
        <template #actions>
            <Button @click="openDialog(null)">
                <UserPlus aria-hidden="true" />
                Invite user
            </Button>
        </template>
        <template #tabs>
            <UsersRolesTabs
                current="users"
                can-users
                :can-roles="canManageRoles"
            />
        </template>
    </AdminPageHeader>

    <DataTable
        table-key="users"
        noun="user"
        :columns="columns"
        :rows="rows"
        :meta="meta"
        :filters="filters"
        :views="views"
        :can-share-views="canShareViews"
        search-placeholder="Search by name or email"
        :row-label="(user: UserRow) => user.name"
        :row-actions="rowActions"
        :bulk-actions="bulkActions"
        selectable
        clickable-rows
        empty-title="No users yet"
        empty-description="Invite the first colleague to give them access."
        @row-click="openFromRow"
    >
        <template #cell-name="{ row, search }">
            <PersonCell
                :name="row.name"
                :secondary="row.email"
                :search="search"
            />
        </template>
        <template #cell-role="{ row }">
            {{ row.role ?? '–' }}
        </template>
        <template #cell-status="{ row }">
            <StatusBadge
                :label="statusLabel[row.status]"
                :tone="statusTone[row.status]"
            />
        </template>
        <template #cell-last_login_at="{ row }">
            <template v-if="row.last_login_at">
                <span class="tabular-nums">{{
                    formatDateTime(row.last_login_at)
                }}</span>
                <span class="ml-2 text-xs text-muted-foreground">{{
                    timeAgo(row.last_login_at)
                }}</span>
            </template>
            <span v-else class="text-muted-foreground">Never</span>
        </template>
        <template #cell-created_at="{ row }">
            <span class="tabular-nums">{{
                formatDateTime(row.created_at ?? '').split(' · ')[0]
            }}</span>
        </template>
        <template #cell-email="{ row, search }">
            <HighlightText :text="row.email" :term="search" />
        </template>
        <template #cell-phone="{ row, search }">
            <HighlightText v-if="row.phone" :text="row.phone" :term="search" />
            <span v-else class="text-muted-foreground">–</span>
        </template>
    </DataTable>

    <UserDialog
        v-model:open="dialogOpen"
        :user="dialogUser"
        :roles="assignableRoles"
    />

    <ConfirmDialog
        v-model:open="confirmOpen"
        :title="confirmTitle"
        :description="confirmDescription"
        :confirm-label="confirmLabel"
        destructive
        :processing="processing"
        @confirm="confirmed"
    />
</template>
