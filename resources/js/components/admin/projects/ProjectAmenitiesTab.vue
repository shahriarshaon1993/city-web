<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import SortableList from '@/components/admin/catalog/SortableList.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { amenityIcons } from '@/lib/amenity-icons';
import { index as amenitiesIndex } from '@/routes/admin/amenities';
import type { AmenityOption } from '@/types';
import { useProjectEditor } from './context';

// Amenities: tick what the project offers (grouped by category) and set the order they appear in
// on the project page. The amenity list itself is managed under Locations › Amenities.
const { form, options, errors } = useProjectEditor();

const groups = computed(() => {
    const result: Array<{ label: string; items: AmenityOption[] }> = [];

    for (const amenity of options.amenities) {
        let group = result.find(
            (item) => item.label === amenity.category_label,
        );

        if (!group) {
            group = { label: amenity.category_label, items: [] };
            result.push(group);
        }

        group.items.push(amenity);
    }

    return result;
});

const selected = computed({
    get: () =>
        form.amenity_ids
            .map((id) => options.amenities.find((amenity) => amenity.id === id))
            .filter((amenity): amenity is AmenityOption => !!amenity)
            .map((amenity) => ({
                id: amenity.id,
                name: amenity.name,
                icon: amenity.icon,
            })),
    set: (rows: Array<{ id: number }>) => {
        form.amenity_ids = rows.map((row) => row.id);
    },
});

function toggle(id: number, checked: boolean): void {
    form.amenity_ids = checked
        ? [...form.amenity_ids, id]
        : form.amenity_ids.filter((item) => item !== id);
}
</script>

<template>
    <div class="grid gap-6">
        <AdminSection
            title="What the project offers"
            description="Shown as icon rows by category on the project page."
        >
            <div id="field-amenity_ids" class="grid gap-6">
                <p v-if="errors.amenity_ids" class="text-sm text-destructive">
                    {{ errors.amenity_ids }}
                </p>
                <p
                    v-if="!options.amenities.length"
                    class="text-sm text-muted-foreground"
                >
                    There are no amenities yet.
                    <Link
                        :href="amenitiesIndex.url()"
                        class="underline underline-offset-4"
                        >Add some under Amenities</Link
                    >.
                </p>

                <fieldset
                    v-for="group in groups"
                    :key="group.label"
                    class="grid gap-2"
                >
                    <legend
                        class="mb-1 text-overline font-medium tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{ group.label }}
                    </legend>
                    <div
                        class="grid gap-x-6 gap-y-1 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        <div
                            v-for="amenity in group.items"
                            :key="amenity.id"
                            class="flex min-h-11 items-center gap-3"
                        >
                            <Checkbox
                                :id="`amenity-${amenity.id}`"
                                :model-value="
                                    form.amenity_ids.includes(amenity.id)
                                "
                                @update:model-value="
                                    (checked) =>
                                        toggle(amenity.id, checked === true)
                                "
                            />
                            <label
                                :for="`amenity-${amenity.id}`"
                                class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 text-sm"
                            >
                                <component
                                    :is="amenityIcons[amenity.icon]"
                                    v-if="amenityIcons[amenity.icon]"
                                    class="size-4 shrink-0 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <span class="truncate">{{ amenity.name }}</span>
                            </label>
                        </div>
                    </div>
                </fieldset>
            </div>
        </AdminSection>

        <AdminSection
            v-if="selected.length > 1"
            title="Order"
            description="The order amenities appear in within their category on the project page."
        >
            <SortableList
                v-model="selected"
                label="Selected amenities"
                :item-label="(item) => item.name"
                compact
            >
                <template #default="{ item }">
                    <span class="flex items-center gap-2 text-sm">
                        <component
                            :is="amenityIcons[item.icon]"
                            v-if="amenityIcons[item.icon]"
                            class="size-4 text-muted-foreground"
                            aria-hidden="true"
                        />
                        {{ item.name }}
                    </span>
                </template>
            </SortableList>
        </AdminSection>
    </div>
</template>
