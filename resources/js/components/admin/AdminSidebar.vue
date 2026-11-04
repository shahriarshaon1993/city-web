<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { PanelLeftClose, PanelLeftOpen } from '@lucide/vue';
import { computed } from 'vue';
import AdminLogoMark from '@/components/admin/AdminLogoMark.vue';
import AdminSidebarItem from '@/components/admin/AdminSidebarItem.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';
import { dashboard } from '@/routes/admin';
import { edit as editProfile } from '@/routes/admin/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);
const { getInitials } = useInitials();
const { isMobile, state, toggleSidebar } = useSidebar();
</script>

<template>
    <!-- Dark in both modes: the --sidebar tokens are not redefined for .dark. -->
    <Sidebar collapsible="icon" class="border-sidebar-border">
        <SidebarHeader
            class="h-16 justify-center border-b border-sidebar-border p-0 px-4"
        >
            <Link
                :href="dashboard()"
                class="flex items-center gap-3 rounded-md group-data-[collapsible=icon]:justify-center"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
                >
                    <AdminLogoMark class="size-6" />
                </span>
                <span
                    class="grid leading-tight group-data-[collapsible=icon]:hidden"
                >
                    <span class="font-display text-base font-medium text-white">
                        {{ page.props.name }}
                    </span>
                    <span
                        class="text-overline tracking-[0.16em] text-sidebar-primary uppercase"
                    >
                        Dashboard
                    </span>
                </span>
            </Link>
        </SidebarHeader>

        <SidebarContent
            class="[scrollbar-width:thin] [scrollbar-color:var(--sidebar-border)_transparent] gap-0 py-3"
        >
            <nav aria-label="Admin sections" class="flex flex-col gap-1">
                <SidebarGroup
                    v-for="group in page.props.navigation"
                    :key="group.label"
                    class="px-3 py-1.5"
                >
                    <SidebarGroupLabel
                        class="text-sidebar-muted h-7 px-3 text-overline tracking-[0.16em] uppercase"
                    >
                        {{ group.label }}
                    </SidebarGroupLabel>
                    <SidebarMenu>
                        <AdminSidebarItem
                            v-for="item in group.items"
                            :key="item.key"
                            :item="item"
                        />
                    </SidebarMenu>
                </SidebarGroup>
            </nav>
        </SidebarContent>

        <SidebarFooter class="gap-1 border-t border-sidebar-border p-3">
            <SidebarMenu v-if="!isMobile">
                <SidebarMenuItem>
                    <SidebarMenuButton
                        class="h-11 gap-3 px-3 text-sidebar-foreground group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-11! group-data-[collapsible=icon]:p-3! hover:bg-(--dark-card) hover:text-white [&>svg]:size-5"
                        :tooltip="
                            state === 'collapsed'
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                        "
                        :aria-label="
                            state === 'collapsed'
                                ? 'Expand sidebar'
                                : 'Collapse sidebar'
                        "
                        @click="toggleSidebar"
                    >
                        <PanelLeftOpen v-if="state === 'collapsed'" />
                        <PanelLeftClose v-else />
                        <span>Collapse</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <Link
                :href="editProfile()"
                class="flex items-center gap-3 rounded-md px-1 py-2 text-left group-data-[collapsible=icon]:justify-center hover:bg-(--dark-card) hover:text-white"
                :title="user.name"
            >
                <Avatar class="size-9 rounded-full">
                    <AvatarImage
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                    />
                    <AvatarFallback
                        class="bg-sidebar-accent text-sm text-white"
                    >
                        {{ getInitials(user.name) }}
                    </AvatarFallback>
                </Avatar>
                <span
                    class="grid min-w-0 leading-tight group-data-[collapsible=icon]:hidden"
                >
                    <span class="truncate text-sm font-medium text-white">{{
                        user.name
                    }}</span>
                    <span class="text-sidebar-muted truncate text-xs">
                        {{ user.roles.join(', ') }}
                    </span>
                </span>
            </Link>
        </SidebarFooter>
    </Sidebar>
</template>
