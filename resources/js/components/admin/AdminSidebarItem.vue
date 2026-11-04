<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { adminIcon } from '@/lib/admin-icons';
import type { NavEntry } from '@/types';

const props = defineProps<{ item: NavEntry }>();

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

// The dashboard lives at /admin, which is the parent of every other screen.
const active = computed(() =>
    props.item.key === 'dashboard'
        ? isCurrentUrl(props.item.href)
        : isCurrentOrParentUrl(props.item.href),
);

const icon = computed(() => adminIcon(props.item.icon));
const countLabel = computed(() =>
    props.item.count === null
        ? null
        : props.item.count > 99
          ? '99+'
          : String(props.item.count),
);
</script>

<template>
    <SidebarMenuItem>
        <!-- CMP-ADM-SIDEBAR: 44 px row, 20 px icon, 3 px left bar on the active item, hover #092C31. -->
        <SidebarMenuButton
            as-child
            :is-active="active"
            :tooltip="item.label"
            class="relative h-11 gap-3 px-3 text-sidebar-foreground group-data-[collapsible=icon]:mx-auto group-data-[collapsible=icon]:size-11! group-data-[collapsible=icon]:p-3! hover:bg-(--dark-card) hover:text-white data-[active=true]:font-semibold data-[active=true]:text-white [&>svg]:size-5"
        >
            <Link
                :href="item.href"
                :aria-current="active ? 'page' : undefined"
                :aria-label="
                    countLabel ? `${item.label}, ${countLabel}` : undefined
                "
            >
                <span
                    v-if="active"
                    class="absolute inset-y-1.5 left-0 w-[3px] rounded-r-full bg-sidebar-primary"
                    aria-hidden="true"
                />
                <component :is="icon" aria-hidden="true" />
                <span class="flex-1 truncate">{{ item.label }}</span>
                <span
                    v-if="countLabel"
                    class="ml-auto rounded-full px-2 py-0.5 text-xs font-medium tabular-nums group-data-[collapsible=icon]:hidden"
                    :class="
                        item.key === 'leads'
                            ? 'bg-sidebar-primary text-sidebar-primary-foreground'
                            : 'bg-sidebar-accent text-stone-200'
                    "
                    aria-hidden="true"
                >
                    {{ countLabel }}
                </span>
                <span
                    v-if="countLabel"
                    class="absolute top-2 right-2 hidden size-2 rounded-full bg-sidebar-primary group-data-[collapsible=icon]:block"
                    aria-hidden="true"
                />
            </Link>
        </SidebarMenuButton>
    </SidebarMenuItem>
</template>
