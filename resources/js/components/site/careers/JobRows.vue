<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { formatDate } from '@/lib/format';
import type { JobRow } from '@/types/careers';

// PG-15 role rows (LAY-CAREERS, direction.md hairline rows): overline
// department, the title as the row's one link (stretched), location · type,
// the deadline with its countdown, a plain arrow. Hover = title to teal and
// the arrow nudge; the focus ring wraps the row. Phones stack the parts.
withDefaults(
    defineProps<{
        roles: JobRow[];
        applyByLabel: string;
        headingAs?: 'h3' | 'h4';
    }>(),
    { headingAs: 'h3' },
);
</script>

<template>
    <ul class="divide-y divide-border border-y border-border">
        <li
            v-for="role in roles"
            :key="role.id"
            class="group/row relative grid gap-x-8 gap-y-2 rounded-xs py-6 has-[a:focus-visible]:outline-2 has-[a:focus-visible]:outline-offset-4 has-[a:focus-visible]:outline-ring has-[a:focus-visible]:outline-solid lg:grid-cols-12 lg:items-center lg:py-7"
        >
            <div class="flex flex-col gap-2 lg:col-span-6">
                <p class="text-overline text-primary uppercase">
                    {{ role.department }}
                </p>
                <component
                    :is="headingAs"
                    class="font-display text-h3 text-balance transition-colors duration-(--dur-fast) group-hover/row:text-primary"
                >
                    <Link
                        :href="role.url"
                        class="outline-none after:absolute after:inset-0"
                    >
                        {{ role.title }}
                    </Link>
                </component>
            </div>
            <p class="text-body text-muted-foreground lg:col-span-3">
                {{ role.location }}
                <span aria-hidden="true"> · </span>
                <span class="sr-only">, </span>
                {{ role.employment_type_label }}
            </p>
            <p class="flex flex-col gap-0.5 text-sm lg:col-span-2">
                <span>
                    {{ applyByLabel }}
                    <time :datetime="role.deadline" class="tabular">
                        {{ formatDate(role.deadline) }}
                    </time>
                </span>
                <span v-if="role.closes_label" class="text-muted-foreground">
                    {{ role.closes_label }}
                </span>
            </p>
            <ArrowRight
                class="hidden size-5 justify-self-end text-muted-foreground transition-[translate,color] duration-(--dur-fast) group-hover/row:translate-x-0.5 group-hover/row:text-primary lg:col-span-1 lg:block"
                stroke-width="1.5"
                aria-hidden="true"
            />
        </li>
    </ul>
</template>
