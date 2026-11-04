<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { AdminCrumb } from '@/types';

defineProps<{ crumbs: AdminCrumb[] }>();
</script>

<template>
    <!-- CMP-ADM-TOPBAR: text-sm, the last item in foreground weight 600. Hidden below 640 px (the page title says where you are). -->
    <Breadcrumb v-if="crumbs.length > 0" class="hidden min-w-0 sm:block">
        <BreadcrumbList class="flex-nowrap text-sm">
            <template v-for="(crumb, index) in crumbs" :key="index">
                <BreadcrumbItem class="min-w-0">
                    <BreadcrumbPage
                        v-if="index === crumbs.length - 1"
                        class="truncate font-semibold"
                    >
                        {{ crumb.title }}
                    </BreadcrumbPage>
                    <BreadcrumbLink v-else-if="crumb.href" as-child>
                        <Link :href="crumb.href">{{ crumb.title }}</Link>
                    </BreadcrumbLink>
                    <span v-else class="truncate text-muted-foreground">{{
                        crumb.title
                    }}</span>
                </BreadcrumbItem>
                <BreadcrumbSeparator v-if="index < crumbs.length - 1" />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
