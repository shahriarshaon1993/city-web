<script setup lang="ts">
import {
    Building,
    Car,
    Footprints,
    GraduationCap,
    Hospital,
    Moon,
    Plane,
    ShoppingBag,
    TrainFront,
    Trees,
} from '@lucide/vue';
import type { Component } from 'vue';
import type { NearbyCategory, NearbyPlace } from '@/types/project';

// CMP-NEAR-01: hairline rows — thin teal category icon · name with the
// category as an overline · "{n} min {drive/walk}"; two columns from 768 px.
defineProps<{
    places: NearbyPlace[];
}>();

const icons: Record<NearbyCategory, Component> = {
    school: GraduationCap,
    hospital: Hospital,
    mall: ShoppingBag,
    mosque: Moon,
    transport: TrainFront,
    park: Trees,
    office: Building,
    airport: Plane,
};
</script>

<template>
    <ul class="grid border-t border-border md:grid-cols-2 md:gap-x-8">
        <li
            v-for="place in places"
            :key="place.id"
            class="flex items-center gap-4 border-b border-border py-4"
        >
            <component
                :is="icons[place.category]"
                class="size-5 shrink-0 text-primary"
                stroke-width="1.5"
                aria-hidden="true"
            />
            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <span class="text-overline text-muted-foreground uppercase">
                    {{ place.category_label }}
                </span>
                <span class="text-body text-pretty">{{ place.name }}</span>
            </div>
            <span
                class="tabular flex shrink-0 items-center gap-1.5 text-sm text-muted-foreground"
            >
                <component
                    :is="place.mode === 'walk' ? Footprints : Car"
                    class="size-4"
                    aria-hidden="true"
                />
                {{ place.minutes }} min {{ place.mode_label }}
            </span>
        </li>
    </ul>
</template>
