<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Mail, Pencil, Trash2, UserCheck, UserPlus, UserX } from '@lucide/vue';
import { computed, ref } from 'vue';
import ListPageHeader from '@/components/admin/ListPageHeader.vue';
import type { ListTab } from '@/components/admin/ListPageHeader.vue';
import DataTable from '@/components/admin/data-table/DataTable.vue';
import HighlightText from '@/components/admin/data-table/HighlightText.vue';
import PersonCell from '@/components/admin/PersonCell.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import UserDialog from '@/components/admin/users/UserDialog.vue';
import UsersRolesTabs from '@/components/admin/users/UsersRolesTabs.vue';
import { Button } from '@/components/ui/button';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { index as binIndex } from '@/routes/admin/recycle-bin';
import {
    activate,
    bulk,
    deactivate,
    destroy,
    index as usersIndex,
    resend,
} from '@/routes/admin/users';
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
    binCount: number;
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

// --- confirm (CMP-ADM-CONFIRM, UX-09) -------------------------------------------------------------
const confirm = useConfirm();
const noun = (count: number): string => (count === 1 ? 'user' : 'users');

async function deactivateOne(user: UserRow): Promise<void> {
    const answer = await confirm({
        title: 'Deactivate this user?',
        subject: user.name,
        description:
            'They can no longer sign in, and any session they have open ends now. Their open leads are reassigned. You can activate them again at any time.',
        confirmLabel: 'Deactivate user',
        destructive: true,
    });

    if (answer) {
        router.post(deactivate.url(user.id), {}, { preserveScroll: true });
    }
}

async function deactivateMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Deactivate ${selection.count} ${noun(selection.count)}?`,
        description:
            'They can no longer sign in, and any session they have open ends now. People you may not deactivate (yourself, or the last Super Admin) are skipped and listed. Open leads are reassigned. You can activate them again at any time.',
        confirmLabel: `Deactivate ${noun(selection.count)}`,
        destructive: true,
    });

    if (answer) {
        postBulk('deactivate', selection);
    }
}

// DEL-02: Delete = Recycle bin. The consequence is named; the toast offers Undo for 8 s.
async function deleteOne(user: UserRow): Promise<void> {
    const answer = await confirm({
        title: 'Delete this user?',
        subject: user.name,
        description:
            'They move to the Recycle bin and can no longer sign in. Their past activity stays in the log. You can restore them from the bin at any time; users are never removed automatically.',
        confirmLabel: 'Delete user',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(user.id), { preserveScroll: true });
    }
}

async function deleteMany(selection: TableSelection): Promise<void> {
    const answer = await confirm({
        title: `Delete ${selection.count} ${noun(selection.count)}?`,
        description:
            'They move to the Recycle bin and can no longer sign in. People you may not delete (yourself, or the last Super Admin) are skipped and listed.',
        confirmLabel: `Delete ${noun(selection.count)}`,
        destructive: true,
        typeToConfirm:
            selection.count > 20 ? `DELETE ${selection.count}` : undefined,
    });

    if (answer) {
        postBulk('delete', selection);
    }
}

function postBulk(
    action: 'activate' | 'deactivate' | 'delete',
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
                  onSelect: () => void deactivateOne(user),
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
            id: 'delete',
            label: 'Delete',
            icon: Trash2,
            destructive: true,
            hidden: !user.can.delete_visible,
            disabledReason: user.can.delete ? null : user.blocked.delete,
            onSelect: () => void deleteOne(user),
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

const { can } = useCan();
const bulkActions = computed<DataTableBulkAction[]>(() => [
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
        run: (selection) => void deactivateMany(selection),
    },
    // Delete → Recycle bin is Super Admin only; others never see the button (HELP-08).
    ...(can('users.delete')
        ? [
              {
                  id: 'delete',
                  label: 'Delete',
                  icon: Trash2,
                  destructive: true,
                  run: (selection: TableSelection) =>
                      void deleteMany(selection),
              },
          ]
        : []),
]);

// CRUD-02: status tabs with counts. They are links that set `filter[status]`, the same state the
// table's own filter chips edit.
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
                ? usersIndex.url()
                : `${usersIndex.url()}?filter[status]=${key}`,
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
    <Head title="Users" />

    <ListPageHeader
        title="Users & roles"
        description="Invite people, set their role and deactivate accounts. A role decides which sections someone can open."
        :tabs="statusTabs"
        :bin-count="can('users.delete') ? binCount : 0"
        :bin-href="binIndex.url({ query: { 'filter[type]': 'user' } })"
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
    </ListPageHeader>

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
</template>
