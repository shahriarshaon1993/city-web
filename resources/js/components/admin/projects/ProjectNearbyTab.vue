<script setup lang="ts">
import { Plus } from '@lucide/vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RepeaterCard from '@/components/admin/catalog/RepeaterCard.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { newKey } from '@/components/admin/catalog/context';
import { Button } from '@/components/ui/button';
import type { NearbyForm } from '@/types';
import {
    countErrors,
    fid,
    moveItem,
    useCardState,
    useProjectEditor,
} from './context';

// Nearby places (FR-PROJ-006, up to 10): a landmark, its category and the travel time, shown as
// "5 min drive" rows on the project page.
const { form, options, errors } = useProjectEditor();
const cards = useCardState();

function add(): void {
    if (form.nearby_places.length >= options.limits.nearby) {
        return;
    }

    const key = newKey('n');
    form.nearby_places.push({
        key,
        name: '',
        category: 'school',
        minutes: null,
        mode: 'drive',
    });
    cards.set(key, true);
}

function summary(place: NearbyForm): string {
    const category = options.nearby_categories.find(
        (item) => item.value === place.category,
    )?.label;
    const mode = options.travel_modes
        .find((item) => item.value === place.mode)
        ?.label.toLowerCase();

    return [
        category,
        place.minutes ? `${place.minutes} min ${mode ?? ''}`.trim() : '',
    ]
        .filter(Boolean)
        .join(' · ');
}
</script>

<template>
    <AdminSection
        title="Nearby places"
        :description="`Schools, hospitals, transport and other landmarks with travel times. Up to ${options.limits.nearby}.`"
    >
        <div id="field-nearby_places" class="grid gap-3">
            <RepeaterCard
                v-for="(place, index) in form.nearby_places"
                :key="place.key"
                :open="
                    cards.isOpen(
                        place.key,
                        countErrors(errors, `nearby_places.${index}`),
                    )
                "
                :title="place.name || `Place ${index + 1}`"
                :summary="summary(place)"
                :errors="countErrors(errors, `nearby_places.${index}`)"
                :noun="`nearby place ${index + 1}`"
                sortable
                :first="index === 0"
                :last="index === form.nearby_places.length - 1"
                @update:open="(open: boolean) => cards.set(place.key, open)"
                @remove="form.nearby_places.splice(index, 1)"
                @move="
                    (delta) =>
                        (form.nearby_places = moveItem(
                            form.nearby_places,
                            index,
                            delta,
                        ))
                "
            >
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <TextField
                            :id="fid('nearby_places', index, 'name')"
                            v-model="place.name"
                            label="Name"
                            required
                            :maxlength="100"
                            :error="errors[`nearby_places.${index}.name`]"
                        />
                    </div>
                    <ChoiceField
                        :id="fid('nearby_places', index, 'category')"
                        v-model="place.category"
                        label="Category"
                        :options="options.nearby_categories"
                        :error="errors[`nearby_places.${index}.category`]"
                    />
                    <TextField
                        :id="fid('nearby_places', index, 'minutes')"
                        v-model="place.minutes"
                        numeric
                        :min="1"
                        :max="600"
                        label="Travel time (minutes)"
                        required
                        :error="errors[`nearby_places.${index}.minutes`]"
                    />
                    <ChoiceField
                        :id="fid('nearby_places', index, 'mode')"
                        v-model="place.mode"
                        label="By"
                        :options="options.travel_modes"
                        :error="errors[`nearby_places.${index}.mode`]"
                    />
                </div>
            </RepeaterCard>

            <p
                v-if="!form.nearby_places.length"
                class="text-sm text-muted-foreground"
            >
                No nearby places yet.
            </p>
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="
                        form.nearby_places.length >= options.limits.nearby
                    "
                    @click="add"
                >
                    <Plus aria-hidden="true" />
                    Add place
                </Button>
            </div>
        </div>
    </AdminSection>
</template>
