<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useId } from 'vue';
import { cn } from '@/lib/utils';

// 12.2: every widget is a card with a title (18 px Manrope 600), an optional filter and a "View all" link.
defineProps<{
    title: string;
    viewAllHref?: string;
    viewAllLabel?: string;
    class?: string;
}>();

const headingId = useId();
</script>

<template>
    <section
        :aria-labelledby="headingId"
        :class="
            cn(
                'flex min-w-0 flex-col rounded-xl border bg-card p-5 md:p-6',
                $props.class,
            )
        "
    >
        <header
            class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-2"
        >
            <h2 :id="headingId" class="font-display text-lg/7 font-semibold">
                {{ title }}
            </h2>
            <div class="flex items-center gap-3">
                <slot name="filter" />
                <Link
                    v-if="viewAllHref"
                    :href="viewAllHref"
                    class="text-sm font-medium text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    {{ viewAllLabel ?? 'View all' }}
                </Link>
            </div>
        </header>
        <slot />
    </section>
</template>
