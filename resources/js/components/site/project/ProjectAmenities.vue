<script setup lang="ts">
import { computed } from 'vue';
import { amenityIcons } from '@/lib/amenity-icons';
import type { Amenity } from '@/types/project';

// PG-04 Amenities: one hairline row per DM-AMEN category — overline label
// and count on the left (1/4), the amenities in two columns on the right,
// each with a thin teal Lucide icon; no icon circles (design/direction.md
// §8). Stacks below 640 px. (Owner, fix 4.2: four narrow columns wrapped.)
const props = defineProps<{
    amenities: Amenity[];
}>();

const groups = computed(() => {
    const byCategory = new Map<string, { label: string; items: Amenity[] }>();

    for (const amenity of props.amenities) {
        const group = byCategory.get(amenity.category);

        if (group) {
            group.items.push(amenity);
        } else {
            byCategory.set(amenity.category, {
                label: amenity.category_label,
                items: [amenity],
            });
        }
    }

    return [...byCategory].map(([category, group]) => ({ category, ...group }));
});
</script>

<template>
    <div v-reveal.stagger class="divide-y divide-border">
        <div
            v-for="group in groups"
            :key="group.category"
            class="grid gap-x-8 gap-y-5 py-7 first:pt-0 last:pb-0 sm:grid-cols-[minmax(0,1fr)_minmax(0,3fr)] lg:py-9"
        >
            <h3
                :id="`amenities-${group.category}`"
                class="flex items-baseline gap-2 text-overline text-muted-foreground uppercase sm:pt-1"
            >
                {{ group.label }}
                <span
                    class="tabular text-muted-foreground/60"
                    aria-hidden="true"
                >
                    {{ String(group.items.length).padStart(2, '0') }}
                </span>
            </h3>
            <ul
                :aria-labelledby="`amenities-${group.category}`"
                class="grid gap-x-8 gap-y-4 sm:grid-cols-2"
            >
                <li
                    v-for="amenity in group.items"
                    :key="amenity.id"
                    class="flex items-center gap-3 text-body"
                >
                    <component
                        :is="amenityIcons[amenity.icon]"
                        v-if="amenityIcons[amenity.icon]"
                        class="size-5 shrink-0 text-primary"
                        :stroke-width="1.5"
                        aria-hidden="true"
                    />
                    <span class="text-pretty">{{ amenity.name }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
