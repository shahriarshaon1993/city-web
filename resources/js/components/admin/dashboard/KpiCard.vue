<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpRight, Minus } from '@lucide/vue';
import { computed } from 'vue';
import { adminIcon } from '@/lib/admin-icons';
import { cn } from '@/lib/utils';
import type { DashboardKpi } from '@/types/admin-dashboard';

// CMP-ADM-KPI: icon tile, corner arrow, value, overline label, delta chip. The whole card is the
// link to the filtered list; a zero is "—" with a helper line instead of a bare 0.
const props = defineProps<{ kpi: DashboardKpi }>();

const icon = computed(() => adminIcon(props.kpi.icon));
const deltaClass = computed(
    () =>
        ({
            up: 'bg-success-50 text-success-700',
            down: 'bg-error-50 text-error-700',
            flat: 'bg-muted text-muted-foreground',
        })[props.kpi.delta?.direction ?? 'flat'],
);
const deltaIcon = computed(
    () =>
        ({ up: ArrowUp, down: ArrowDown, flat: Minus })[
            props.kpi.delta?.direction ?? 'flat'
        ],
);
</script>

<template>
    <component
        :is="kpi.href ? Link : 'div'"
        :href="kpi.href ?? undefined"
        :class="
            cn(
                'group relative flex min-h-40 flex-col rounded-xl border bg-card p-5 text-card-foreground',
                kpi.href &&
                    'transition-colors hover:bg-accent/60 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
            )
        "
    >
        <div class="flex items-start justify-between">
            <span
                class="flex size-10 items-center justify-center rounded-lg bg-muted text-muted-foreground"
            >
                <component :is="icon" class="size-5" aria-hidden="true" />
            </span>
            <ArrowUpRight
                v-if="kpi.href"
                class="size-4 text-muted-foreground transition-colors group-hover:text-foreground"
                aria-hidden="true"
            />
        </div>

        <p
            class="mt-4 font-display text-4xl/10 font-extrabold tabular-nums"
            :class="kpi.value === null && 'text-muted-foreground'"
        >
            {{ kpi.value ?? '—' }}
            <span v-if="kpi.value === null" class="sr-only">
                (nothing to show yet)
            </span>
        </p>
        <p
            class="mt-1 text-overline tracking-[0.16em] text-muted-foreground uppercase"
        >
            {{ kpi.label }}
        </p>

        <div class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs">
            <span
                v-if="kpi.delta"
                :class="
                    cn(
                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-medium',
                        deltaClass,
                    )
                "
            >
                <component :is="deltaIcon" class="size-3" aria-hidden="true" />
                {{ kpi.delta.text }}
            </span>
            <span v-else-if="kpi.helper" class="text-muted-foreground">
                {{ kpi.helper }}
            </span>
        </div>
    </component>
</template>
