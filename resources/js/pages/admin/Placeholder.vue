<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import { adminIcon } from '@/lib/admin-icons';

defineProps<{
    title: string;
    description: string;
    icon: string;
    phase: string;
}>();
</script>

<template>
    <Head :title="title" />

    <AdminPageHeader :title="title" :description="description" />

    <!-- HELP-08: an empty screen says why it is empty and when it arrives. -->
    <section
        class="mx-auto flex max-w-xl flex-col items-center rounded-lg border border-dashed bg-card px-6 py-14 text-center"
    >
        <span
            class="flex size-12 items-center justify-center rounded-lg bg-muted text-muted-foreground"
        >
            <component
                :is="adminIcon(icon)"
                class="size-6"
                aria-hidden="true"
            />
        </span>
        <h2 class="mt-4 text-lg font-semibold">
            {{ title }} arrives in phase {{ phase }}
        </h2>
        <p class="mt-2 max-w-md text-sm text-muted-foreground">
            This menu item is already in place so you can see how the dashboard
            will be organised. There is nothing to manage here yet.
        </p>
    </section>
</template>
