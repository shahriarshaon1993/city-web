<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/site/Breadcrumbs.vue';
import { home } from '@/routes';
import type { Breadcrumb } from '@/types/site';

// Stub content page until the builder pages of 6.3.
defineProps<{
    title: string;
    intro: string;
    breadcrumbs: Breadcrumb[];
}>();
</script>

<template>
    <div class="mx-auto flex max-w-3xl flex-col gap-4 px-6 py-10">
        <Breadcrumbs :items="breadcrumbs" />
        <h1 class="mt-6 font-display text-h1">
            {{ title }}
        </h1>
        <p class="text-lg">{{ intro }}</p>
        <p>
            <Link
                :href="home()"
                class="font-medium text-primary underline underline-offset-4"
            >
                Home
            </Link>
        </p>
    </div>
</template>
