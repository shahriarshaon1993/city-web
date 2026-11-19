<script setup lang="ts">
import { VisAxis, VisLine, VisXYContainer } from '@unovis/vue';
import { computed, ref } from 'vue';
import {
    ChartContainer,
    ChartCrosshair,
    ChartTooltip,
    ChartTooltipContent,
    componentToString,
} from '@/components/ui/chart';
import type { ChartConfig } from '@/components/ui/chart';
import { cn } from '@/lib/utils';
import type { DashboardTrend } from '@/types/admin-dashboard';

// CMP-ADM-CHART line chart of ADM-DASH "Leads trend": one line for all leads, or one per lead type
// (the four busiest, the rest as "Other"). Colours are the `--chart-1…5` tokens.
const props = defineProps<{ trend: DashboardTrend }>();

type Mode = 'all' | 'types';

const mode = ref<Mode>('all');

const SLOTS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

const lines = computed(() =>
    mode.value === 'all'
        ? [
              {
                  key: 'all',
                  label: 'All leads',
                  color: SLOTS[0],
                  values: props.trend.series.all,
              },
          ]
        : props.trend.series.types.map((type, index) => ({
              key: type.key,
              label: type.label,
              color: SLOTS[index % SLOTS.length],
              values: type.values,
          })),
);

type Point = { date: number } & Record<string, number>;

const data = computed<Point[]>(() =>
    props.trend.dates.map((date, index) => {
        const point: Point = { date: Date.parse(`${date}T00:00:00Z`) };

        for (const line of lines.value) {
            point[line.key] = line.values[index] ?? 0;
        }

        return point;
    }),
);

const config = computed<ChartConfig>(() =>
    Object.fromEntries(
        lines.value.map((line) => [
            line.key,
            { label: line.label, color: line.color },
        ]),
    ),
);

const DAY = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'UTC',
    day: 'numeric',
    month: 'short',
});
const DAY_LONG = new Intl.DateTimeFormat('en-GB', {
    timeZone: 'UTC',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

const yAccessors = computed(() =>
    lines.value.map((line) => (point: Point) => point[line.key]),
);
const colors = computed(() => lines.value.map((line) => line.color));

const tooltip = computed(() =>
    componentToString(config.value, ChartTooltipContent, {
        labelFormatter: (value: number | Date) =>
            DAY_LONG.format(new Date(value)),
    }),
);

const peak = computed(() => {
    const values = props.trend.series.all;
    const max = Math.max(...values);

    return max === 0
        ? null
        : DAY_LONG.format(
              new Date(
                  Date.parse(
                      `${props.trend.dates[values.indexOf(max)]}T00:00:00Z`,
                  ),
              ),
          );
});
</script>

<template>
    <div>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div
                role="group"
                aria-label="Chart lines"
                class="inline-flex rounded-md border p-0.5"
            >
                <button
                    v-for="option in [
                        { value: 'all', label: 'All leads' },
                        { value: 'types', label: 'By type' },
                    ] as { value: Mode; label: string }[]"
                    :key="option.value"
                    type="button"
                    :aria-pressed="mode === option.value"
                    :class="
                        cn(
                            'min-h-8 rounded-[5px] px-3 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                            mode === option.value && 'bg-muted text-foreground',
                        )
                    "
                    @click="mode = option.value"
                >
                    {{ option.label }}
                </button>
            </div>

            <ul
                v-if="mode === 'types'"
                class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground"
            >
                <li
                    v-for="line in lines"
                    :key="line.key"
                    class="inline-flex items-center gap-1.5"
                >
                    <span
                        class="size-2.5 rounded-full"
                        :style="{ backgroundColor: line.color }"
                        aria-hidden="true"
                    />
                    {{ line.label }}
                </li>
            </ul>
        </div>

        <p class="sr-only">
            {{ trend.total }} leads in the last {{ trend.days }} days<template
                v-if="peak"
                >, the most on {{ peak }}</template
            >.
        </p>

        <ChartContainer
            :config="config"
            cursor
            class="aspect-auto h-64 w-full"
            aria-hidden="true"
        >
            <VisXYContainer
                :data="data"
                :margin="{ top: 8, right: 8, bottom: 0, left: 0 }"
                :y-domain="[0, undefined]"
            >
                <VisLine
                    :x="(point: Point) => point.date"
                    :y="yAccessors"
                    :color="colors"
                    :duration="0"
                />
                <VisAxis
                    type="x"
                    :x="(point: Point) => point.date"
                    :tick-line="false"
                    :domain-line="false"
                    :grid-line="false"
                    :num-ticks="6"
                    :tick-format="
                        (value: number) => DAY.format(new Date(value))
                    "
                />
                <VisAxis
                    type="y"
                    :tick-line="false"
                    :domain-line="false"
                    :num-ticks="4"
                    :tick-format="
                        (value: number) =>
                            Number.isInteger(value) ? String(value) : ''
                    "
                />
                <ChartTooltip />
                <ChartCrosshair
                    :template="tooltip"
                    :color="colors"
                    :x="(point: Point) => point.date"
                    :y="yAccessors"
                />
            </VisXYContainer>
        </ChartContainer>
    </div>
</template>
