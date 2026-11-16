<script setup lang="ts">
import { Copy, Plus } from '@lucide/vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import AssetField from '@/components/admin/catalog/AssetField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RepeaterCard from '@/components/admin/catalog/RepeaterCard.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { newKey } from '@/components/admin/catalog/context';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { formatBdt, formatNumber } from '@/lib/format';
import type { UnitTypeForm } from '@/types';
import {
    countErrors,
    fid,
    moveItem,
    useCardState,
    useProjectEditor,
} from './context';

// Unit types (FR-PROJ-002, 1–20; none for land): each a collapsible card with a summary line, its
// floor plan, a plan description for screen readers (A11Y-D11) and an optional price that leaves
// the server only when both the project and the unit show prices (AC-PROJ-01).
const { form, options, errors } = useProjectEditor();
const cards = useCardState();

function blank(): UnitTypeForm {
    return {
        key: newKey('u'),
        name: '',
        size_sft: null,
        size_sft_max: null,
        bedrooms: null,
        bathrooms: null,
        balconies: null,
        facing: '',
        floor_range: '',
        floor_plan_asset_id: null,
        plan_description: '',
        plan_3d_asset_id: null,
        price_bdt: null,
        show_price: false,
        availability: 'available',
    };
}

function add(): void {
    if (form.unit_types.length >= options.limits.unit_types) {
        return;
    }

    const unit = blank();
    form.unit_types.push(unit);
    cards.set(unit.key, true);
}

// CRUD-05: duplicate a unit type inside the editor (a new card without an id, so it is created on save).
function duplicate(unit: UnitTypeForm): void {
    if (form.unit_types.length >= options.limits.unit_types) {
        return;
    }

    const copy: UnitTypeForm = {
        ...unit,
        key: newKey('u'),
        id: undefined,
        name: `${unit.name} copy`.slice(0, 60),
    };
    form.unit_types.splice(form.unit_types.indexOf(unit) + 1, 0, copy);
    cards.set(copy.key, true);
}

function summary(unit: UnitTypeForm): string {
    const size = Number(unit.size_sft);
    const max = Number(unit.size_sft_max);
    const parts: string[] = [];

    if (size > 0) {
        parts.push(
            max > size
                ? `${formatNumber(size)}–${formatNumber(max)} sft`
                : `${formatNumber(size)} sft`,
        );
    }

    if (unit.bedrooms !== null && unit.bedrooms !== '') {
        parts.push(`${unit.bedrooms} bed`);
    }

    if (unit.show_price && Number(unit.price_bdt) > 0) {
        parts.push(formatBdt(Number(unit.price_bdt)));
    }

    return parts.join(' · ');
}
</script>

<template>
    <AdminSection
        title="Unit types"
        :description="`Apartment, suite or shop types with size, plan and availability. Up to ${options.limits.unit_types}. A non-land project needs at least one to publish.`"
    >
        <div id="field-unit_types" class="grid gap-3">
            <p v-if="errors.unit_types" class="text-sm text-destructive">
                {{ errors.unit_types }}
            </p>

            <RepeaterCard
                v-for="(unit, index) in form.unit_types"
                :key="unit.key"
                :open="
                    cards.isOpen(
                        unit.key,
                        countErrors(errors, `unit_types.${index}`),
                    )
                "
                :title="unit.name || `Unit type ${index + 1}`"
                :summary="summary(unit)"
                :errors="countErrors(errors, `unit_types.${index}`)"
                :noun="`unit type ${index + 1}`"
                sortable
                :first="index === 0"
                :last="index === form.unit_types.length - 1"
                @update:open="(open: boolean) => cards.set(unit.key, open)"
                @remove="form.unit_types.splice(index, 1)"
                @move="
                    (delta) =>
                        (form.unit_types = moveItem(
                            form.unit_types,
                            index,
                            delta,
                        ))
                "
            >
                <div class="grid gap-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <TextField
                            :id="fid('unit_types', index, 'name')"
                            v-model="unit.name"
                            label="Name"
                            required
                            :maxlength="60"
                            placeholder="Type A"
                            :error="errors[`unit_types.${index}.name`]"
                        />
                        <ChoiceField
                            :id="fid('unit_types', index, 'availability')"
                            v-model="unit.availability"
                            label="Availability"
                            :options="options.availabilities"
                            :error="errors[`unit_types.${index}.availability`]"
                        />
                        <TextField
                            :id="fid('unit_types', index, 'size_sft')"
                            v-model="unit.size_sft"
                            numeric
                            :min="1"
                            label="Size (sft)"
                            required
                            :error="errors[`unit_types.${index}.size_sft`]"
                        />
                        <TextField
                            :id="fid('unit_types', index, 'size_sft_max')"
                            v-model="unit.size_sft_max"
                            numeric
                            :min="1"
                            label="Largest size (sft)"
                            helper="Only when this type comes in a range."
                            :error="errors[`unit_types.${index}.size_sft_max`]"
                        />
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <TextField
                            :id="fid('unit_types', index, 'bedrooms')"
                            v-model="unit.bedrooms"
                            numeric
                            :min="0"
                            :max="20"
                            label="Bedrooms"
                            :error="errors[`unit_types.${index}.bedrooms`]"
                        />
                        <TextField
                            :id="fid('unit_types', index, 'bathrooms')"
                            v-model="unit.bathrooms"
                            numeric
                            :min="0"
                            :max="20"
                            label="Bathrooms"
                            :error="errors[`unit_types.${index}.bathrooms`]"
                        />
                        <TextField
                            :id="fid('unit_types', index, 'balconies')"
                            v-model="unit.balconies"
                            numeric
                            :min="0"
                            :max="20"
                            label="Balconies"
                            :error="errors[`unit_types.${index}.balconies`]"
                        />
                    </div>

                    <div class="grid gap-5 md:grid-cols-2">
                        <TextField
                            :id="fid('unit_types', index, 'facing')"
                            v-model="unit.facing"
                            label="Facing"
                            :maxlength="40"
                            :error="errors[`unit_types.${index}.facing`]"
                        />
                        <TextField
                            :id="fid('unit_types', index, 'floor_range')"
                            v-model="unit.floor_range"
                            label="Floors"
                            :maxlength="60"
                            placeholder="3rd to 12th floor"
                            :error="errors[`unit_types.${index}.floor_range`]"
                        />
                    </div>

                    <FormField
                        :id="fid('unit_types', index, 'floor_plan_asset_id')"
                        label="Floor plan"
                        helper="Needed to publish. A drawing as an image or SVG."
                        :error="
                            errors[`unit_types.${index}.floor_plan_asset_id`]
                        "
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                :id="
                                    fid(
                                        'unit_types',
                                        index,
                                        'floor_plan_asset_id',
                                    )
                                "
                                v-model="unit.floor_plan_asset_id"
                                :kinds="['image', 'svg']"
                                choose-label="Choose floor plan"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>

                    <FormField
                        :id="fid('unit_types', index, 'plan_description')"
                        label="Plan description"
                        helper="Room sizes in words, for visitors who cannot see the drawing. Up to 300 characters."
                        :counter="{
                            length: unit.plan_description.length,
                            max: 300,
                        }"
                        :error="errors[`unit_types.${index}.plan_description`]"
                    >
                        <template #default="{ describedby, invalid }">
                            <Textarea
                                :id="
                                    fid('unit_types', index, 'plan_description')
                                "
                                v-model="unit.plan_description"
                                rows="3"
                                maxlength="300"
                                :aria-invalid="invalid || undefined"
                                :aria-describedby="describedby"
                            />
                        </template>
                    </FormField>

                    <FormField
                        :id="fid('unit_types', index, 'plan_3d_asset_id')"
                        label="3D plan"
                        helper="Optional."
                        :error="errors[`unit_types.${index}.plan_3d_asset_id`]"
                    >
                        <template #default="{ describedby, invalid }">
                            <AssetField
                                :id="
                                    fid('unit_types', index, 'plan_3d_asset_id')
                                "
                                v-model="unit.plan_3d_asset_id"
                                :kinds="['image']"
                                choose-label="Choose 3D plan"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>

                    <div
                        class="grid gap-5 rounded-lg bg-surface-subtle p-4 md:grid-cols-2"
                    >
                        <TextField
                            :id="fid('unit_types', index, 'price_bdt')"
                            v-model="unit.price_bdt"
                            numeric
                            :min="100000"
                            label="Price (৳)"
                            helper="Whole taka, at least 1,00,000. Optional."
                            :error="errors[`unit_types.${index}.price_bdt`]"
                        />
                        <SwitchRow
                            :id="fid('unit_types', index, 'show_price')"
                            v-model="unit.show_price"
                            label="Show this price"
                            helper="Visitors see it only when the project's Pricing tab also shows prices."
                            :error="errors[`unit_types.${index}.show_price`]"
                        />
                    </div>

                    <div>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            :disabled="
                                form.unit_types.length >=
                                options.limits.unit_types
                            "
                            @click="duplicate(unit)"
                        >
                            <Copy aria-hidden="true" />
                            Duplicate this type
                        </Button>
                    </div>
                </div>
            </RepeaterCard>

            <p
                v-if="!form.unit_types.length"
                class="text-sm text-muted-foreground"
            >
                {{
                    form.segment === 'land'
                        ? 'Land projects need no unit types. Put plot sizes in the custom facts.'
                        : 'No unit types yet. Add the first one.'
                }}
            </p>
            <div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="
                        form.unit_types.length >= options.limits.unit_types
                    "
                    @click="add"
                >
                    <Plus aria-hidden="true" />
                    Add unit type
                </Button>
            </div>
        </div>
    </AdminSection>
</template>
