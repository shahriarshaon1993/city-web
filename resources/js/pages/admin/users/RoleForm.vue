<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy, index, store, update } from '@/routes/admin/roles';
import type { PermissionGroup } from '@/types';

// ADM-SCR-16 role editor (crud.md: Roles = full page). System roles open read-only.
const props = defineProps<{
    role: {
        id: number;
        name: string;
        system: boolean;
        users_count: number;
    } | null;
    groups: PermissionGroup[];
    selected: string[];
    required: string;
}>();

const form = useForm({
    name: props.role?.name ?? '',
    permissions: [...props.selected],
});

const readonly = computed(() => props.role?.system === true);
const title = computed(() =>
    props.role === null
        ? 'New role'
        : readonly.value
          ? props.role.name
          : `Edit ${props.role.name}`,
);

const chosen = computed(() => new Set(form.permissions));

function setPermission(name: string, checked: boolean): void {
    form.permissions = checked
        ? [...new Set([...form.permissions, name])]
        : form.permissions.filter((item) => item !== name);
}

function groupState(group: PermissionGroup): boolean | 'indeterminate' {
    const count = group.permissions.filter((permission) =>
        chosen.value.has(permission.name),
    ).length;

    return count === 0
        ? false
        : count === group.permissions.length
          ? true
          : 'indeterminate';
}

function setGroup(group: PermissionGroup, checked: boolean): void {
    const names = group.permissions.map((permission) => permission.name);
    const rest = form.permissions.filter((name) => !names.includes(name));

    form.permissions = checked
        ? [...rest, ...names]
        : [...rest, ...names.filter((name) => name === props.required)];
}

function submit(): void {
    if (props.role) {
        form.patch(update.url(props.role.id), { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}

const deleteOpen = ref(false);
const deleting = ref(false);

function confirmDelete(): void {
    if (!props.role) {
        return;
    }

    deleting.value = true;
    router.delete(destroy.url(props.role.id), {
        onFinish: () => {
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
}

const deleteReason = computed(() =>
    props.role && props.role.users_count > 0
        ? `${props.role.users_count} ${props.role.users_count === 1 ? 'user has' : 'users have'} this role. Give them another role first.`
        : null,
);
</script>

<template>
    <Head :title="title" />

    <AdminPageHeader
        :title="title"
        :description="
            readonly
                ? 'This is a system role. Its permissions are fixed so every installation behaves the same.'
                : 'Name the role and tick what it may do. People with this role see only the sections these permissions open.'
        "
    />

    <form class="flex max-w-[1100px] flex-col gap-6" @submit.prevent="submit">
        <div
            v-if="readonly"
            class="flex items-start gap-3 rounded-lg border bg-muted px-4 py-3 text-sm"
            role="note"
        >
            <Lock class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <p>
                System roles cannot be edited or deleted. To give someone a
                different set of permissions,
                <Link :href="index.url()" class="underline"
                    >create a custom role</Link
                >.
            </p>
        </div>

        <section class="grid gap-2 rounded-lg border bg-card p-5 md:p-6">
            <Label for="role-name">
                Role name
                <span
                    v-if="!readonly"
                    class="text-destructive"
                    aria-hidden="true"
                    >*</span
                >
            </Label>
            <Input
                id="role-name"
                v-model="form.name"
                class="max-w-md"
                maxlength="60"
                :readonly="readonly"
                :required="!readonly"
                :aria-invalid="!!form.errors.name || undefined"
            />
            <p
                v-if="!readonly"
                class="text-xs text-muted-foreground tabular-nums"
            >
                {{ form.name.length }} / 60
            </p>
            <InputError :message="form.errors.name" />
        </section>

        <section
            v-for="group in groups"
            :key="group.label"
            class="grid gap-4 rounded-lg border bg-card p-5 md:p-6 xl:grid-cols-3 xl:gap-10"
        >
            <div class="flex items-start gap-3">
                <Checkbox
                    :id="`group-${group.label}`"
                    :model-value="groupState(group)"
                    :disabled="readonly"
                    :aria-label="`All permissions for ${group.label}`"
                    class="mt-0.5"
                    @update:model-value="
                        (value) => setGroup(group, value === true)
                    "
                />
                <h2 class="text-base font-semibold">{{ group.label }}</h2>
            </div>
            <ul class="grid gap-1 sm:grid-cols-2 xl:col-span-2">
                <li
                    v-for="permission in group.permissions"
                    :key="permission.name"
                >
                    <label
                        :for="`perm-${permission.name}`"
                        class="flex min-h-9 items-center gap-3 rounded-sm px-2 text-sm"
                        :class="
                            readonly || permission.name === required
                                ? ''
                                : 'cursor-pointer hover:bg-accent'
                        "
                    >
                        <Checkbox
                            :id="`perm-${permission.name}`"
                            :model-value="chosen.has(permission.name)"
                            :disabled="readonly || permission.name === required"
                            @update:model-value="
                                (value) =>
                                    setPermission(
                                        permission.name,
                                        value === true,
                                    )
                            "
                        />
                        <span>
                            {{ permission.label }}
                            <span
                                v-if="permission.name === required"
                                class="block text-xs text-muted-foreground"
                            >
                                Always on: it opens the page everyone lands on
                                after signing in.
                            </span>
                        </span>
                    </label>
                </li>
            </ul>
        </section>

        <InputError :message="form.errors.permissions" />

        <div class="flex flex-wrap items-center gap-3">
            <Button
                v-if="!readonly"
                type="submit"
                :disabled="form.processing || form.name.trim() === ''"
            >
                <Spinner v-if="form.processing" />
                {{ role ? 'Save role' : 'Create role' }}
            </Button>
            <Button as-child variant="outline">
                <Link :href="index.url()">{{
                    readonly ? 'Back to roles' : 'Cancel'
                }}</Link>
            </Button>
            <div v-if="role && !readonly" class="ml-auto">
                <Button
                    type="button"
                    variant="outline"
                    class="text-destructive"
                    :aria-disabled="deleteReason ? 'true' : undefined"
                    :aria-describedby="
                        deleteReason ? 'role-delete-reason' : undefined
                    "
                    @click="deleteReason ? undefined : (deleteOpen = true)"
                >
                    Delete role
                </Button>
                <p
                    v-if="deleteReason"
                    id="role-delete-reason"
                    class="mt-1 max-w-xs text-xs text-muted-foreground"
                >
                    {{ deleteReason }}
                </p>
            </div>
        </div>
    </form>

    <ConfirmDialog
        v-model:open="deleteOpen"
        :title="`Delete the role “${role?.name ?? ''}”?`"
        description="The role and its permission set are removed for good. Nobody holds this role, so no one loses access."
        confirm-label="Delete role"
        destructive
        :processing="deleting"
        @confirm="confirmDelete"
    />
</template>
