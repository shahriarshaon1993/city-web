<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Check, TriangleAlert } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as projects } from '@/routes/site/projects';

// The page behind the links in the newsletter mails (confirm, unsubscribe):
// the left-aligned thank-you pattern of direction.md §6 — overline with a thin
// icon, light H1, one sentence, teal primary action. `noindex,nofollow`.
defineProps<{
    state: 'confirmed' | 'unsubscribed' | 'invalid';
    title: string;
    message: string;
}>();
</script>

<template>
    <section
        aria-labelledby="newsletter-status-heading"
        class="mx-auto max-w-content px-4 pt-12 pb-(--section-md) sm:px-6 lg:px-8 lg:pt-20 xl:px-10"
    >
        <div class="flex max-w-3xl flex-col gap-5">
            <p
                class="flex items-center gap-2 text-overline text-primary uppercase"
            >
                <TriangleAlert
                    v-if="state === 'invalid'"
                    class="size-4"
                    aria-hidden="true"
                />
                <Check v-else class="size-4" aria-hidden="true" />
                Newsletter
            </p>
            <h1 id="newsletter-status-heading" class="text-h1 text-balance">
                {{ title }}
            </h1>
            <p class="max-w-prose text-lead text-pretty text-muted-foreground">
                {{ message }}
            </p>
            <div class="mt-3 flex flex-wrap gap-3">
                <Button as-child size="lg">
                    <Link :href="projects()">Explore our projects</Link>
                </Button>
                <Button as-child variant="link" size="lg">
                    <Link :href="home()" class="text-foreground">
                        Back to home
                    </Link>
                </Button>
            </div>
        </div>
    </section>
</template>
