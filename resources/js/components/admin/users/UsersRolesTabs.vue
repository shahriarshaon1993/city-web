<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { cn } from '@/lib/utils';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as usersIndex } from '@/routes/admin/users';

// Users and Roles are two pages of one screen (ADM-SCR-16); an Admin holds `users.manage` only
// and so sees just the first tab.
defineProps<{
    current: 'users' | 'roles';
    canUsers: boolean;
    canRoles: boolean;
}>();

const tab =
    'inline-flex h-11 items-center border-b-2 px-1 text-sm font-medium transition-colors';
</script>

<template>
    <nav aria-label="Users and roles" class="-mb-px flex gap-6">
        <Link
            v-if="canUsers"
            :href="usersIndex.url()"
            :aria-current="current === 'users' ? 'page' : undefined"
            :class="
                cn(
                    tab,
                    current === 'users'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                )
            "
        >
            Users
        </Link>
        <Link
            v-if="canRoles"
            :href="rolesIndex.url()"
            :aria-current="current === 'roles' ? 'page' : undefined"
            :class="
                cn(
                    tab,
                    current === 'roles'
                        ? 'border-primary text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                )
            "
        >
            Roles
        </Link>
    </nav>
</template>
