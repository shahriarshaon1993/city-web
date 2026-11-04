<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { LogOut, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useInitials } from '@/composables/useInitials';
import { logout } from '@/routes';
import { edit as editProfile } from '@/routes/admin/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();

// Clear cached pages so the next person on this browser sees nothing of this session.
function flush(): void {
    router.flushAll();
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger
            class="flex size-11 items-center justify-center rounded-full"
            aria-label="Account menu"
        >
            <Avatar class="size-9">
                <AvatarImage
                    v-if="user.avatar"
                    :src="user.avatar"
                    :alt="user.name"
                />
                <AvatarFallback
                    class="bg-primary text-sm text-primary-foreground"
                >
                    {{ getInitials(user.name) }}
                </AvatarFallback>
            </Avatar>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-64">
            <DropdownMenuLabel class="font-normal">
                <span class="block truncate text-sm font-semibold">{{
                    user.name
                }}</span>
                <span class="block truncate text-xs text-muted-foreground">{{
                    user.email
                }}</span>
                <span class="mt-1 block text-xs text-muted-foreground">{{
                    user.roles.join(', ')
                }}</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem as-child>
                <Link :href="editProfile()" class="w-full cursor-pointer">
                    <UserRound class="mr-2 size-4" />
                    Profile
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child>
                <Link
                    :href="logout()"
                    class="w-full cursor-pointer"
                    as="button"
                    data-test="logout-button"
                    @click="flush"
                >
                    <LogOut class="mr-2 size-4" />
                    Sign out
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
