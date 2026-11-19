<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, Check } from '@lucide/vue';
import { computed, ref } from 'vue';
import { dismissGettingStarted } from '@/actions/App/Http/Controllers/Admin/DashboardController';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import type { GettingStartedStep } from '@/types/admin-dashboard';

// HELP-05: the checklist of a new site. Steps tick themselves from what exists in the database;
// the card is dismissible and disappears once every step is done.
const props = defineProps<{ steps: GettingStartedStep[] }>();

const done = computed(() => props.steps.filter((step) => step.done).length);
const percent = computed(() =>
    Math.round((done.value / props.steps.length) * 100),
);
const dismissing = ref(false);

function dismiss(): void {
    router.post(
        dismissGettingStarted.url(),
        {},
        {
            preserveScroll: true,
            onStart: () => (dismissing.value = true),
            onFinish: () => (dismissing.value = false),
        },
    );
}
</script>

<template>
    <section
        aria-labelledby="getting-started-heading"
        class="rounded-xl border bg-card p-5 md:p-6"
    >
        <header
            class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3"
        >
            <div class="min-w-0">
                <h2
                    id="getting-started-heading"
                    class="font-display text-lg/7 font-semibold"
                >
                    Getting started
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Nine steps to get the site ready for visitors. Each one
                    opens the screen where you do it.
                </p>
            </div>
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :loading="dismissing"
                @click="dismiss"
            >
                Hide checklist
            </Button>
        </header>

        <div class="mt-4 flex items-center gap-3 text-sm">
            <div
                class="h-1.5 w-full max-w-xs overflow-hidden rounded-full bg-muted"
                role="progressbar"
                aria-label="Getting started progress"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-valuenow="percent"
            >
                <div
                    class="h-full rounded-full bg-primary"
                    :style="{ width: `${percent}%` }"
                />
            </div>
            <p class="shrink-0 text-muted-foreground tabular-nums">
                {{ done }} of {{ steps.length }} done
            </p>
        </div>

        <ol class="mt-4 grid gap-x-8 md:grid-cols-2">
            <li v-for="step in steps" :key="step.key" class="border-b">
                <Link
                    :href="step.href"
                    class="group flex min-h-14 items-start gap-3 rounded-md py-3 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <span
                        :class="
                            cn(
                                'mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full border',
                                step.done &&
                                    'border-success-400 bg-success-50 text-success-700',
                            )
                        "
                        aria-hidden="true"
                    >
                        <Check v-if="step.done" class="size-3" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span
                            :class="
                                cn(
                                    'block text-sm font-medium',
                                    step.done &&
                                        'text-muted-foreground line-through',
                                )
                            "
                        >
                            {{ step.label }}
                            <span class="sr-only">
                                {{ step.done ? '(done)' : '(not done yet)' }}
                            </span>
                        </span>
                        <span
                            class="mt-0.5 block text-xs text-muted-foreground"
                        >
                            {{ step.hint }}
                        </span>
                    </span>
                    <ArrowRight
                        class="mt-0.5 size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                        aria-hidden="true"
                    />
                </Link>
            </li>
        </ol>
    </section>
</template>
