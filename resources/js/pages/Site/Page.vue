<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

// Stub content page until the builder pages of 6.3.
defineProps<{
    title: string;
    intro: string;
}>();
</script>

<template>
    <div class="mx-auto flex max-w-3xl flex-col gap-4 px-6 py-16">
        <h1 class="font-display text-5xl font-extrabold tracking-tight">
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
