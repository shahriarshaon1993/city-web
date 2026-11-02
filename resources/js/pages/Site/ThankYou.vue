<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as projects } from '@/routes/site/projects';

// PG-22 landing for the lead forms' redirect (FR-LEAD-005). 7.1 shows the
// per-form confirmation only; 7.2 adds next steps, related projects,
// WhatsApp and the `generate_lead` dataLayer push.
defineProps<{
    form: string;
    title: string;
    message: string;
}>();
</script>

<template>
    <div
        class="mx-auto flex min-h-[60svh] max-w-content flex-col justify-center gap-8 px-4 py-16 sm:px-6 lg:px-8 lg:py-24 xl:px-10"
    >
        <div class="flex max-w-2xl flex-col gap-5">
            <p class="text-overline text-primary uppercase">Request received</p>
            <h1 class="text-h1 text-balance">{{ title }}</h1>
            <p class="text-lead text-pretty text-muted-foreground">
                {{ message }}
            </p>
        </div>
        <div class="flex flex-wrap gap-3">
            <Button as-child size="lg">
                <Link :href="projects()">Explore our projects</Link>
            </Button>
            <Button as-child variant="outline" size="lg">
                <Link :href="home()">Back to home</Link>
            </Button>
        </div>
    </div>
</template>
