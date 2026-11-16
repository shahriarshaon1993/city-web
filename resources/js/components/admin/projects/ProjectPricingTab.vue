<script setup lang="ts">
import { computed } from 'vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { formatBdt } from '@/lib/format';
import { useProjectEditor } from './context';

// Pricing (FR-PROJ-009, AC-PROJ-01): hidden by default. A price reaches the public site only when
// this switch is on AND the unit type shows its own price. "From" is the lowest shown unit price,
// or a figure set by hand (land projects have no unit types).
const { form, errors } = useProjectEditor();

const fromLabel = computed(() => {
    const value = Number(form.price_from_bdt);

    return value > 0 ? formatBdt(value) : 'no figure yet';
});
</script>

<template>
    <div class="grid gap-6">
        <AdminSection
            title="Showing prices"
            description="The company decides, per project and per unit type, whether a price is public."
        >
            <div class="grid gap-5">
                <SwitchRow
                    id="field-show_price"
                    v-model="form.show_price"
                    label="Show prices on the website"
                    helper="Off: no price appears anywhere — not on pages, in search results or in the page data. On: only unit types that also show their price are priced."
                    :error="errors.show_price"
                />
                <TextField
                    id="field-price_note"
                    v-model="form.price_note"
                    label="Price note"
                    :maxlength="80"
                    placeholder="Excluding registration"
                    helper="A short line shown with the price. Up to 80 characters."
                    :error="errors.price_note"
                />
            </div>
        </AdminSection>

        <AdminSection
            title="“Starting from” price"
            description="The figure on project cards and the title block."
        >
            <div class="grid gap-5">
                <SwitchRow
                    id="field-price_from_manual"
                    v-model="form.price_from_manual"
                    label="Set the figure by hand"
                    helper="Off: the lowest price of the unit types that show their price. On: the amount below, e.g. for land projects."
                    :error="errors.price_from_manual"
                />
                <TextField
                    v-if="form.price_from_manual"
                    id="field-price_from_bdt"
                    v-model="form.price_from_bdt"
                    numeric
                    :min="100000"
                    label="Starting from (৳)"
                    helper="Whole taka, at least 1,00,000."
                    :error="errors.price_from_bdt"
                />
                <p v-else class="text-sm text-muted-foreground">
                    Current figure:
                    <strong class="font-medium text-foreground">{{
                        fromLabel
                    }}</strong>
                    <template v-if="!form.show_price">
                        (not public while prices are off)</template
                    >. It updates when you save the unit types.
                </p>
            </div>
        </AdminSection>
    </div>
</template>
