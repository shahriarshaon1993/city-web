<script setup lang="ts">
import { BadgeCheck, Check, Copy } from '@lucide/vue';
import { computed, ref } from 'vue';
import { formatKatha, formatMonth, formatNumber } from '@/lib/format';
import { notify } from '@/lib/toast';
import type { ProjectFacts } from '@/types/project';

// CMP-FACTS-01 "At a glance": a hairline <dl> grid (2 columns, 3 from
// 640 px), overline label over a medium value; the address spans the row.
// The RAJUK approval number has a seal and a copy button; custom facts are
// appended. Typographic, no icon tiles (design/direction.md §6).
const props = defineProps<{
    facts: ProjectFacts;
    customFacts: { label: string; value: string }[];
}>();

type Fact = { key: string; label: string; value: string; wide?: boolean };

const items = computed<Fact[]>(() => {
    const f = props.facts;
    const count = (value: number | null): string | null =>
        value === null ? null : formatNumber(value);
    const list: [string, string, string | null][] = [
        [
            'land_area_katha',
            'Land area',
            f.land_area_katha === null ? null : formatKatha(f.land_area_katha),
        ],
        ['storeys', 'Storeys', f.storeys],
        ['total_units', 'Total units', count(f.total_units)],
        ['units_per_floor', 'Units per floor', count(f.units_per_floor)],
        ['lifts', 'Lifts', count(f.lifts)],
        ['car_parks', 'Car parks', count(f.car_parks)],
        ['basements', 'Basements', count(f.basements)],
        [
            'road_width_ft',
            'Road width',
            f.road_width_ft === null
                ? null
                : `${formatNumber(f.road_width_ft)} ft`,
        ],
        ['facing', 'Facing', f.facing],
        ['orientation', 'Orientation', f.orientation],
        [
            'launch_date',
            'Launched',
            f.launch_date === null ? null : formatMonth(f.launch_date),
        ],
        [
            'handover_date',
            'Handover',
            f.handover_date === null ? null : formatMonth(f.handover_date),
        ],
    ];

    return [
        ...(f.address
            ? [
                  {
                      key: 'address',
                      label: 'Address',
                      value: f.address,
                      wide: true,
                  },
              ]
            : []),
        ...list
            .filter(
                (entry): entry is [string, string, string] => entry[2] !== null,
            )
            .map(([key, label, value]) => ({ key, label, value })),
        ...props.customFacts.map((fact, index) => ({
            key: `custom-${index}`,
            label: fact.label,
            value: fact.value,
        })),
    ];
});

const copied = ref(false);
let copiedTimer: ReturnType<typeof setTimeout> | undefined;

async function copyRajuk(): Promise<void> {
    if (!props.facts.rajuk_approval_no) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.facts.rajuk_approval_no);
        copied.value = true;
        notify.success('RAJUK approval number copied');
        clearTimeout(copiedTimer);
        copiedTimer = setTimeout(() => (copied.value = false), 2000);
    } catch {
        notify.error('Could not copy. Select the number and copy it instead.');
    }
}
</script>

<template>
    <dl v-reveal class="grid grid-cols-2 gap-x-6 sm:grid-cols-3 lg:gap-x-8">
        <div
            v-for="item in items"
            :key="item.key"
            :class="[
                'flex flex-col gap-1.5 border-t border-border py-5',
                item.wide && 'col-span-2 sm:col-span-3',
            ]"
        >
            <dt class="text-overline text-muted-foreground uppercase">
                {{ item.label }}
            </dt>
            <dd class="font-display text-fact text-pretty">
                {{ item.value }}
            </dd>
        </div>
        <div
            v-if="facts.rajuk_approval_no"
            class="col-span-2 flex flex-col gap-1.5 border-t border-border py-5 sm:col-span-3"
        >
            <dt
                class="inline-flex items-center gap-1.5 text-overline text-muted-foreground uppercase"
            >
                <BadgeCheck class="size-4 text-primary" aria-hidden="true" />
                RAJUK approval no.
            </dt>
            <dd class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span class="font-display text-fact break-all">{{
                    facts.rajuk_approval_no
                }}</span>
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center gap-1.5 text-sm font-medium text-primary underline-offset-4 hover:underline"
                    aria-label="Copy RAJUK approval number"
                    @click="copyRajuk"
                >
                    <component
                        :is="copied ? Check : Copy"
                        class="size-4"
                        aria-hidden="true"
                    />
                    {{ copied ? 'Copied' : 'Copy' }}
                </button>
            </dd>
        </div>
    </dl>
</template>
