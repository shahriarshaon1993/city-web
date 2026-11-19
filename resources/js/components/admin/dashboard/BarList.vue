<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { DashboardBar } from '@/types/admin-dashboard';

// CMP-ADM-CHART (horizontal bars): labels stay readable at any length and each row opens the
// matching filtered list, which an SVG axis label could not do.
const props = defineProps<{ rows: DashboardBar[] }>();

const max = computed(() => Math.max(1, ...props.rows.map((row) => row.count)));
</script>

<template>
    <ul class="space-y-1">
        <li v-for="row in rows" :key="row.key">
            <component
                :is="row.href ? Link : 'div'"
                :href="row.href ?? undefined"
                class="group grid min-h-11 grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1 rounded-md px-2 py-1.5 text-sm"
                :class="
                    row.href &&
                    'hover:bg-accent/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none'
                "
            >
                <span class="truncate" :title="row.label">{{ row.label }}</span>
                <span class="font-medium tabular-nums">
                    {{ row.count.toLocaleString('en-GB') }}
                    <span class="sr-only">{{
                        row.count === 1 ? 'lead' : 'leads'
                    }}</span>
                </span>
                <span
                    class="col-span-2 h-1.5 overflow-hidden rounded-full bg-muted"
                    aria-hidden="true"
                >
                    <span
                        class="block h-full rounded-full bg-chart-1"
                        :style="{
                            width: `${row.count === 0 ? 0 : Math.max(2, (row.count / max) * 100)}%`,
                        }"
                    />
                </span>
            </component>
        </li>
    </ul>
</template>
