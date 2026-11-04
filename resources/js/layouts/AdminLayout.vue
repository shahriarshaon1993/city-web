<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AdminOfflineBanner from '@/components/admin/AdminOfflineBanner.vue';
import AdminSidebar from '@/components/admin/AdminSidebar.vue';
import AdminTopbar from '@/components/admin/AdminTopbar.vue';
import { Toaster } from '@/components/ui/sonner';
import { SidebarProvider } from '@/components/ui/sidebar';

// `sidebarOpen` comes from the sidebar_state cookie, so the collapsed state survives a reload.
const sidebarOpen = usePage().props.sidebarOpen;
</script>

<template>
    <SidebarProvider :default-open="sidebarOpen" class="bg-surface-subtle">
        <a
            href="#admin-main"
            class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-primary-foreground"
        >
            Skip to content
        </a>
        <AdminSidebar />
        <div class="flex min-w-0 flex-1 flex-col">
            <AdminTopbar />
            <AdminOfflineBanner />
            <!-- Canvas padding 32 px, 24 px below 1280, 16 px below 768 (design/admin.md §12.1). -->
            <main
                id="admin-main"
                tabindex="-1"
                class="flex-1 p-4 outline-none md:p-6 xl:p-8"
            >
                <slot />
            </main>
        </div>
        <Toaster />
    </SidebarProvider>
</template>
