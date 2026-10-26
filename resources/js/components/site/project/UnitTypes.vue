<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Expand } from '@lucide/vue';
import { computed, ref } from 'vue';
import ResponsiveImage from '@/components/site/ResponsiveImage.vue';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatBdtCompact, formatNumber, formatSft } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { UnitAvailability, UnitType } from '@/types/project';

// CMP-UNIT-01: underline tabs per unit type (a native select below 640 px,
// same state), each panel a floor plan on greige (button → lightbox) beside
// a hairline spec list and "Enquire about Type A". A price shows only when
// the server sent one (AC-PROJ-01/02); sold-out types are greyed with
// "(sold out)". Every panel is in the HTML (force-mounted, hidden when
// inactive), so crawlers and no-JS visitors keep the specs.
const props = defineProps<{
    units: UnitType[];
    /** e.g. "/contact?project=gemcon-lakeview"; `&unit=` is added (7.1 opens the form instead). */
    enquireUrl: string;
}>();

const emit = defineEmits<{
    openPlan: [index: number];
}>();

const active = ref(String(props.units[0]?.id ?? ''));

const dotClass: Record<UnitAvailability, string> = {
    available: 'bg-success-700',
    limited: 'bg-warning-700',
    sold_out: 'bg-stone-500',
};

function specs(unit: UnitType): { label: string; value: string }[] {
    const rows: [string, string | null][] = [
        [
            'Size',
            unit.size_sft_max
                ? `${formatNumber(unit.size_sft)}–${formatSft(unit.size_sft_max)}`
                : formatSft(unit.size_sft),
        ],
        [
            'Bedrooms',
            unit.bedrooms === null ? null : formatNumber(unit.bedrooms),
        ],
        [
            'Bathrooms',
            unit.bathrooms === null ? null : formatNumber(unit.bathrooms),
        ],
        [
            'Balconies',
            unit.balconies === null ? null : formatNumber(unit.balconies),
        ],
        ['Facing', unit.facing],
        ['Floors', unit.floor_range],
    ];

    return rows
        .filter((row): row is [string, string] => row[1] !== null)
        .map(([label, value]) => ({ label, value }));
}

function tabLabel(unit: UnitType): string {
    return unit.availability === 'sold_out'
        ? `${unit.name} (sold out)`
        : unit.name;
}

const plansWithImage = computed(() =>
    props.units.filter((unit) => unit.floor_plan !== null),
);

function openPlan(unit: UnitType): void {
    emit(
        'openPlan',
        plansWithImage.value.findIndex((item) => item.id === unit.id),
    );
}

function enquireHref(unit: UnitType): string {
    return `${props.enquireUrl}&unit=${encodeURIComponent(unit.name)}`;
}
</script>

<template>
    <Tabs v-model="active" class="gap-0">
        <label
            v-if="units.length > 1"
            class="mb-6 flex flex-col gap-2 sm:hidden"
        >
            <span class="text-overline text-muted-foreground uppercase"
                >Unit type</span
            >
            <select
                v-model="active"
                class="h-11 w-full rounded-md border border-input bg-background px-3 text-body"
            >
                <option
                    v-for="unit in units"
                    :key="unit.id"
                    :value="String(unit.id)"
                >
                    {{ tabLabel(unit) }}
                </option>
            </select>
        </label>
        <TabsList
            v-if="units.length > 1"
            aria-label="Unit types"
            class="mb-8 hidden overflow-x-auto sm:flex lg:mb-10"
        >
            <TabsTrigger
                v-for="unit in units"
                :key="unit.id"
                :value="String(unit.id)"
                :class="
                    cn(
                        'font-medium',
                        unit.availability === 'sold_out' &&
                            'text-muted-foreground/70',
                    )
                "
            >
                {{ unit.name }}
                <span
                    v-if="unit.availability === 'sold_out'"
                    class="font-normal"
                >
                    (sold out)
                </span>
            </TabsTrigger>
        </TabsList>

        <TabsContent
            v-for="unit in units"
            :key="unit.id"
            :value="String(unit.id)"
            force-mount
            class="grid gap-8 data-[state=inactive]:hidden md:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:gap-10"
        >
            <div>
                <button
                    v-if="unit.floor_plan"
                    type="button"
                    class="group relative block w-full rounded-xl bg-surface-subtle p-4 sm:p-6"
                    :aria-label="`Open the floor plan of ${unit.name}`"
                    :aria-describedby="
                        unit.plan_description ? `plan-${unit.id}` : undefined
                    "
                    @click="openPlan(unit)"
                >
                    <ResponsiveImage
                        :image="unit.floor_plan"
                        preset="floor-plan"
                        class="rounded-md"
                        img-class="mix-blend-multiply"
                    />
                    <span
                        class="absolute right-3 bottom-3 inline-flex size-11 items-center justify-center rounded-full bg-background text-foreground ring-1 ring-border transition-colors duration-(--dur-fast) group-hover:text-primary"
                        aria-hidden="true"
                    >
                        <Expand class="size-4" />
                    </span>
                </button>
                <p
                    v-if="unit.plan_description"
                    :id="`plan-${unit.id}`"
                    class="sr-only"
                >
                    {{ unit.plan_description }}
                </p>
            </div>

            <div class="flex flex-col">
                <h3 class="font-display text-h3">{{ unit.name }}</h3>
                <p
                    class="mt-2 inline-flex items-center gap-2 text-overline text-muted-foreground uppercase"
                >
                    <span
                        :class="[
                            'size-1.5 rounded-full',
                            dotClass[unit.availability],
                        ]"
                        aria-hidden="true"
                    />
                    {{ unit.availability_label }}
                </p>

                <dl class="mt-6 divide-y divide-border border-y border-border">
                    <div
                        v-for="spec in specs(unit)"
                        :key="spec.label"
                        class="flex items-baseline justify-between gap-4 py-3"
                    >
                        <dt class="text-sm text-muted-foreground">
                            {{ spec.label }}
                        </dt>
                        <dd class="tabular text-right text-body font-medium">
                            {{ spec.value }}
                        </dd>
                    </div>
                    <div
                        class="flex min-h-14 items-baseline justify-between gap-4 py-3"
                    >
                        <dt class="text-sm text-muted-foreground">Price</dt>
                        <dd
                            v-if="unit.price_bdt !== null"
                            class="tabular font-display text-price"
                        >
                            {{ formatBdtCompact(unit.price_bdt) }}
                        </dd>
                        <dd v-else class="text-body text-muted-foreground">
                            Price on request
                        </dd>
                    </div>
                </dl>

                <Button
                    as-child
                    variant="outline"
                    class="group/enquire mt-6 self-start"
                >
                    <Link :href="enquireHref(unit)">
                        Enquire about {{ unit.name }}
                        <ArrowRight
                            class="transition-transform duration-(--dur-base) group-hover/enquire:translate-x-0.5"
                            aria-hidden="true"
                        />
                    </Link>
                </Button>
            </div>
        </TabsContent>
    </Tabs>
</template>
