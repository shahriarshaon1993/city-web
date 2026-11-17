<script setup lang="ts">
import { Plus, Trash2 } from '@lucide/vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import MonthField from '@/components/admin/catalog/MonthField.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { newKey } from '@/components/admin/catalog/context';
import { Button } from '@/components/ui/button';
import { fid, useProjectEditor } from './context';

// Facts: the "At a glance" list of the project page (FR-PROJ-001) — map position, building facts,
// dates (month precision), approvals and the team, plus up to 12 custom label / value facts.
const { form, options, errors } = useProjectEditor();

function addFact(): void {
    if (form.custom_facts.length < options.limits.custom_facts) {
        form.custom_facts.push({ key: newKey('f'), label: '', value: '' });
    }
}
</script>

<template>
    <div class="grid gap-6">
        <AdminSection
            title="Location on the map"
            description="Needed to publish. The pin on the project page and the map listing use it."
        >
            <div class="grid gap-5">
                <TextField
                    id="field-address"
                    v-model="form.address"
                    label="Address"
                    :maxlength="255"
                    helper="Shown with the map. Up to 255 characters."
                    :error="errors.address"
                />
                <div class="grid gap-5 md:grid-cols-2">
                    <TextField
                        id="field-latitude"
                        v-model="form.latitude"
                        numeric
                        :step="0.0000001"
                        :min="20.5"
                        :max="26.7"
                        label="Latitude"
                        helper="Inside Bangladesh: 20.5 to 26.7, e.g. 23.8103."
                        :error="errors.latitude"
                    />
                    <TextField
                        id="field-longitude"
                        v-model="form.longitude"
                        numeric
                        :step="0.0000001"
                        :min="88"
                        :max="92.7"
                        label="Longitude"
                        helper="Inside Bangladesh: 88.0 to 92.7, e.g. 90.4125."
                        :error="errors.longitude"
                    />
                </div>
            </div>
        </AdminSection>

        <AdminSection
            title="Building"
            description="Shown in the “At a glance” list. Leave a field empty to hide it."
        >
            <div class="grid gap-5 md:grid-cols-2">
                <TextField
                    id="field-land_area_katha"
                    v-model="form.land_area_katha"
                    numeric
                    :step="0.01"
                    :min="0"
                    label="Land area (katha)"
                    helper="Up to 2 decimals."
                    :error="errors.land_area_katha"
                />
                <TextField
                    id="field-storeys"
                    v-model="form.storeys"
                    label="Storeys"
                    :maxlength="40"
                    placeholder="2B + G + 12"
                    helper="Basements, ground and upper floors."
                    :error="errors.storeys"
                />
                <TextField
                    id="field-total_units"
                    v-model="form.total_units"
                    numeric
                    :min="0"
                    :max="65535"
                    label="Total units"
                    :error="errors.total_units"
                />
                <TextField
                    id="field-units_per_floor"
                    v-model="form.units_per_floor"
                    numeric
                    :min="0"
                    :max="255"
                    label="Units per floor"
                    :error="errors.units_per_floor"
                />
                <TextField
                    id="field-lifts"
                    v-model="form.lifts"
                    numeric
                    :min="0"
                    :max="255"
                    label="Lifts"
                    :error="errors.lifts"
                />
                <TextField
                    id="field-car_parks"
                    v-model="form.car_parks"
                    numeric
                    :min="0"
                    :max="65535"
                    label="Car parks"
                    :error="errors.car_parks"
                />
                <TextField
                    id="field-basements"
                    v-model="form.basements"
                    numeric
                    :min="0"
                    :max="255"
                    label="Basements"
                    :error="errors.basements"
                />
                <TextField
                    id="field-road_width_ft"
                    v-model="form.road_width_ft"
                    numeric
                    :min="0"
                    :max="65535"
                    label="Road width (ft)"
                    :error="errors.road_width_ft"
                />
                <TextField
                    id="field-facing"
                    v-model="form.facing"
                    label="Facing"
                    :maxlength="40"
                    placeholder="South"
                    :error="errors.facing"
                />
                <TextField
                    id="field-orientation"
                    v-model="form.orientation"
                    label="Orientation"
                    :maxlength="40"
                    :error="errors.orientation"
                />
            </div>
        </AdminSection>

        <AdminSection title="Dates" description="Month and year only.">
            <div class="grid gap-5 md:grid-cols-2">
                <MonthField
                    id="field-launch_date"
                    v-model="form.launch_date"
                    label="Launch"
                    :error="errors.launch_date"
                />
                <MonthField
                    id="field-handover_date"
                    v-model="form.handover_date"
                    :min="form.launch_date || undefined"
                    label="Handover"
                    helper="Not before the launch month. Hidden once the project is handed over."
                    :error="errors.handover_date"
                />
            </div>
        </AdminSection>

        <AdminSection
            title="Approvals and team"
            description="Shown under “Design and delivery” on the project page."
        >
            <div class="grid gap-5 md:grid-cols-2">
                <TextField
                    id="field-rajuk_approval_no"
                    v-model="form.rajuk_approval_no"
                    label="RAJUK approval no."
                    :maxlength="80"
                    :error="errors.rajuk_approval_no"
                />
                <TextField
                    id="field-architect"
                    v-model="form.architect"
                    label="Architect"
                    :maxlength="150"
                    :error="errors.architect"
                />
                <TextField
                    id="field-structural_engineer"
                    v-model="form.structural_engineer"
                    label="Structural engineer"
                    :maxlength="150"
                    :error="errors.structural_engineer"
                />
                <TextField
                    id="field-contractor"
                    v-model="form.contractor"
                    label="Contractor"
                    :maxlength="150"
                    :error="errors.contractor"
                />
            </div>
        </AdminSection>

        <AdminSection
            title="Custom facts"
            :description="`Anything else buyers ask about. Up to ${options.limits.custom_facts}; land projects use these for plot sizes.`"
        >
            <div id="field-custom_facts" class="grid gap-3">
                <p
                    v-if="!form.custom_facts.length"
                    class="text-sm text-muted-foreground"
                >
                    No custom facts yet.
                </p>
                <div
                    v-for="(fact, index) in form.custom_facts"
                    :key="fact.key"
                    class="grid gap-3 sm:grid-cols-[1fr_1fr_auto] sm:items-start"
                >
                    <TextField
                        :id="fid('custom_facts', index, 'label')"
                        v-model="fact.label"
                        label="Label"
                        :maxlength="60"
                        :error="errors[`custom_facts.${index}.label`]"
                    />
                    <TextField
                        :id="fid('custom_facts', index, 'value')"
                        v-model="fact.value"
                        label="Value"
                        :maxlength="120"
                        :error="errors[`custom_facts.${index}.value`]"
                    />
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        class="text-destructive hover:text-destructive sm:mt-7"
                        :aria-label="`Remove custom fact ${index + 1}`"
                        @click="form.custom_facts.splice(index, 1)"
                    >
                        <Trash2 aria-hidden="true" />
                    </Button>
                </div>
                <div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        :disabled="
                            form.custom_facts.length >=
                            options.limits.custom_facts
                        "
                        @click="addFact"
                    >
                        <Plus aria-hidden="true" />
                        Add fact
                    </Button>
                </div>
            </div>
        </AdminSection>
    </div>
</template>
