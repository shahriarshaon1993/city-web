<script setup lang="ts">
import { computed, useId } from 'vue';
import { Switch } from '@/components/ui/switch';
import { useListing } from '@/composables/useProjectListing';
import { cn } from '@/lib/utils';

// CMP-FILT-01 "More": booking state (single choice) and featured only.
const props = defineProps<{
    idPrefix: string;
}>();

const listing = useListing();
const bookingId = useId();
const featuredId = `${props.idPrefix}-featured`;

const bookingOptions = computed(() => listing.facets.value.booking);

function chooseBooking(value: string): void {
    listing.update({
        booking: listing.state.booking === value ? null : value,
    });
}
</script>

<template>
    <div class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
            <p
                :id="bookingId"
                class="text-overline text-muted-foreground uppercase"
            >
                Availability
            </p>
            <div
                role="group"
                :aria-labelledby="bookingId"
                class="flex flex-wrap gap-2"
            >
                <button
                    v-for="option in bookingOptions"
                    :key="option.value"
                    type="button"
                    :data-filter-key="`${props.idPrefix}:booking:${option.value}`"
                    :aria-pressed="listing.state.booking === option.value"
                    :disabled="
                        option.count === 0 &&
                        listing.state.booking !== option.value
                    "
                    :class="
                        cn(
                            'inline-flex min-h-11 items-center gap-1.5 rounded-md border border-border px-3 text-sm transition-colors duration-(--dur-fast) hover:not-disabled:border-foreground/40 disabled:cursor-not-allowed disabled:opacity-50',
                            listing.state.booking === option.value &&
                                'border-foreground bg-foreground text-background hover:not-disabled:border-foreground',
                        )
                    "
                    @click="chooseBooking(option.value)"
                >
                    {{ option.label }}
                    <span class="tabular opacity-70">{{ option.count }}</span>
                </button>
            </div>
        </div>

        <div class="flex min-h-11 items-center justify-between gap-4">
            <label :for="featuredId" class="flex-1 text-body">
                Featured projects only
                <span class="tabular ml-1 text-sm text-muted-foreground">
                    {{ listing.facets.value.featured_count }}
                </span>
            </label>
            <Switch
                :id="featuredId"
                :data-filter-key="featuredId"
                :model-value="listing.state.featured"
                :disabled="
                    listing.facets.value.featured_count === 0 &&
                    !listing.state.featured
                "
                @update:model-value="listing.update({ featured: $event })"
            />
        </div>
    </div>
</template>
