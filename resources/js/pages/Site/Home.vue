<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

// Phase 0.1 stub; replaced by the home blocks in 2.x.
defineProps<{
    title: string;
    lead: string;
}>();
</script>

<template>
    <main
        class="mx-auto flex min-h-svh max-w-3xl flex-col justify-center gap-4 px-6"
    >
        <Head :title="title" />
        <h1 class="font-display text-5xl font-extrabold tracking-tight">
            {{ title }}
        </h1>
        <p class="text-lg">{{ lead }}</p>
    </main>
</template>
