<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Bell, ExternalLink, Search } from '@lucide/vue';
import { computed } from 'vue';
import AdminBreadcrumbs from '@/components/admin/AdminBreadcrumbs.vue';
import AdminThemeToggle from '@/components/admin/AdminThemeToggle.vue';
import AdminUserMenu from '@/components/admin/AdminUserMenu.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { home } from '@/routes';
import type { AdminCrumb } from '@/types';

const page = usePage();
const crumbs = computed(
    () => (page.props.breadcrumbs as AdminCrumb[] | undefined) ?? [],
);
</script>

<template>
    <!-- CMP-ADM-TOPBAR: 64 px, sticky, background/90 + blur, hairline below. -->
    <header
        class="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-3 border-b bg-background/90 px-4 backdrop-blur md:px-6 xl:px-8"
    >
        <SidebarTrigger class="-ml-2 size-11 lg:hidden" />
        <AdminBreadcrumbs :crumbs="crumbs" />

        <div class="ml-auto flex items-center gap-1 sm:gap-2">
            <!-- Placeholder until the command palette (18.2): disabled, and says why (HELP-08). -->
            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        type="button"
                        aria-disabled="true"
                        class="hidden h-11 w-80 cursor-not-allowed items-center gap-2 rounded-md border bg-background px-3 text-sm text-muted-foreground opacity-70 lg:flex"
                    >
                        <Search class="size-4" aria-hidden="true" />
                        <span class="flex-1 text-left"
                            >Search the dashboard</span
                        >
                        <kbd
                            class="rounded border bg-muted px-1.5 py-0.5 text-xs"
                            aria-hidden="true"
                            >/</kbd
                        >
                    </button>
                </TooltipTrigger>
                <TooltipContent
                    >Search arrives with the command palette (phase
                    18.2).</TooltipContent
                >
            </Tooltip>

            <Button
                variant="outline"
                size="sm"
                as-child
                class="hidden sm:inline-flex"
            >
                <a :href="home().url" target="_blank" rel="noopener">
                    View site
                    <ExternalLink class="size-4" aria-hidden="true" />
                    <span class="sr-only">(opens in a new tab)</span>
                </a>
            </Button>

            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        aria-disabled="true"
                        aria-label="Notifications (none yet)"
                        class="cursor-not-allowed"
                    >
                        <Bell class="size-5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent
                    >Notifications arrive in phase 15.3.</TooltipContent
                >
            </Tooltip>

            <AdminThemeToggle />
            <AdminUserMenu />
        </div>
    </header>
</template>
