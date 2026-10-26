<script setup lang="ts">
import { Search } from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { fieldControlClass } from '@/components/ui/input';
import { useListing } from '@/composables/useProjectListing';
import { cn } from '@/lib/utils';

// CMP-FILT-01 Area (multi): type-to-filter list grouped by city, counts per
// area, at most 5 (spec/search.md edge cases) — the rest disable once 5 are
// chosen. Each tick is a selection (`push`, FR-SRCH-003).
const MAX_AREAS = 5;

const props = defineProps<{
    /** Unique per instance (popover vs sheet). */
    idPrefix: string;
}>();

const listing = useListing();
const query = ref('');
const hintId = useId();

const groups = computed(() => {
    const needle = query.value.trim().toLowerCase();

    return listing.facets.value.cities
        .map((city) => ({
            city: city.city,
            areas: city.areas.filter(
                (area) => !needle || area.label.toLowerCase().includes(needle),
            ),
        }))
        .filter((city) => city.areas.length > 0);
});

const full = computed(() => listing.state.area.length >= MAX_AREAS);

function toggle(value: string, checked: boolean): void {
    const areas = checked
        ? [...listing.state.area, value]
        : listing.state.area.filter((area) => area !== value);

    listing.update({ area: areas.sort() });
}
</script>

<template>
    <div class="flex flex-col gap-3">
        <label :for="`${props.idPrefix}-area-search`" class="sr-only">
            Find an area
        </label>
        <span class="relative block">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <input
                :id="`${props.idPrefix}-area-search`"
                v-model="query"
                type="search"
                autocomplete="off"
                placeholder="Find an area"
                :aria-describedby="hintId"
                :class="cn(fieldControlClass, 'h-11 pl-9')"
            />
        </span>
        <p :id="hintId" class="text-sm text-muted-foreground">
            Choose up to {{ MAX_AREAS }} areas.
        </p>

        <div
            data-lenis-prevent
            class="-mx-1 flex max-h-72 flex-col gap-3 overflow-y-auto px-1"
        >
            <fieldset v-for="group in groups" :key="group.city">
                <legend
                    class="pb-1 text-overline text-muted-foreground uppercase"
                >
                    {{ group.city }}
                </legend>
                <ul>
                    <li v-for="area in group.areas" :key="area.value">
                        <label
                            :class="
                                cn(
                                    'flex min-h-11 cursor-pointer items-center gap-3 text-body',
                                    ((full &&
                                        !listing.state.area.includes(
                                            area.value,
                                        )) ||
                                        (area.count === 0 &&
                                            !listing.state.area.includes(
                                                area.value,
                                            ))) &&
                                        'cursor-not-allowed opacity-50',
                                )
                            "
                        >
                            <Checkbox
                                :data-filter-key="`${props.idPrefix}:area:${area.value}`"
                                :model-value="
                                    listing.state.area.includes(area.value)
                                "
                                :disabled="
                                    !listing.state.area.includes(area.value) &&
                                    (full || area.count === 0)
                                "
                                @update:model-value="
                                    toggle(area.value, $event === true)
                                "
                            />
                            <span class="flex-1">{{ area.label }}</span>
                            <span class="tabular text-sm text-muted-foreground">
                                {{ area.count }}
                            </span>
                        </label>
                    </li>
                </ul>
            </fieldset>
            <p
                v-if="groups.length === 0"
                class="py-2 text-sm text-muted-foreground"
            >
                No matching area
            </p>
        </div>
    </div>
</template>
