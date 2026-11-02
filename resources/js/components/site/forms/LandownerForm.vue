<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FileUpload from '@/components/forms/FileUpload.vue';
import FormField from '@/components/forms/FormField.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import MessageField from '@/components/forms/MessageField.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
import SelectField from '@/components/forms/SelectField.vue';
import { Input } from '@/components/ui/input';
import { useLeadForm } from '@/composables/useLeadForm';
import {
    accepted,
    email,
    maxLength,
    minLength,
    numberBetween,
    phone,
    required,
} from '@/lib/forms/rules';
import type { FormLocation } from '@/types/forms';
import type { SitePageProps } from '@/types/site';

// `landowner` (PG-10, FR-LEAD): name*, phone*, email, land location*, land
// size (katha)*, road width (ft), plot facing, ownership, up to three
// documents (PDF/JPG/PNG, 5 MB each), message, consent*.
const props = defineProps<{
    location: FormLocation;
}>();

const forms = usePage<SitePageProps>().props.forms;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;
const files = forms.limits.files;

const lead = useLeadForm({
    slug: 'landowner',
    location: props.location,
    initial: {
        name: '',
        phone_country: forms.default_country,
        phone: '',
        email: '',
        land_location: '',
        land_size_katha: '',
        road_width_ft: '',
        plot_facing: '',
        ownership: '',
        documents: [] as File[],
        message: '',
        consent: false,
    },
    rules: {
        name: [
            required('Enter your full name.'),
            minLength(2, 'Your name needs at least 2 letters.'),
        ],
        phone: [required('Enter your phone number.'), phone(dial)],
        email: [email()],
        land_location: [
            required('Enter where the land is: an area or an address.'),
            maxLength(190, 'The location can be up to 190 characters.'),
        ],
        land_size_katha: [
            required('Enter the land area in katha.'),
            numberBetween(
                0,
                10000,
                {
                    number: 'Enter the land area as a number, e.g. 5.5.',
                    range: 'The land area must be more than 0 and up to 10,000 katha.',
                },
                true,
            ),
        ],
        road_width_ft: [
            numberBetween(1, 500, {
                number: 'Enter the road width in whole feet, e.g. 20.',
                range: 'The road width must be between 1 and 500 feet.',
            }),
        ],
        message: [
            maxLength(
                forms.limits.message_max,
                `Your message can be up to ${forms.limits.message_max} characters.`,
            ),
        ],
        consent: [accepted()],
    },
});

const { form, summary, showSummary, summaryItems } = lead;

/** Refused files never reach the form; the reason shows under the field. */
function onRejected(message: string): void {
    form.setError('documents', message);
}
</script>

<template>
    <form
        class="flex flex-col gap-5"
        novalidate
        @submit.prevent="lead.submit()"
    >
        <ErrorSummary v-if="showSummary" ref="summary" :items="summaryItems" />

        <PersonFields />

        <FormField
            :id="lead.fieldId('land_location')"
            label="Where is the land?"
            required
            helper="An area, or a full address."
            :error="lead.error('land_location')"
        >
            <template #default="{ id, describedby, invalid }">
                <Input
                    :id="id"
                    v-model="form.land_location"
                    name="land_location"
                    list="landowner-areas"
                    maxlength="190"
                    :aria-invalid="invalid || undefined"
                    :aria-describedby="describedby"
                    @blur="lead.onBlur('land_location')"
                    @input="lead.onChange('land_location')"
                />
                <datalist id="landowner-areas">
                    <option
                        v-for="area in forms.choices.areas"
                        :key="area.value"
                        :value="area.value"
                    >
                        {{ area.label }}
                    </option>
                </datalist>
            </template>
        </FormField>

        <div class="grid gap-5 sm:grid-cols-2">
            <FormField
                :id="lead.fieldId('land_size_katha')"
                label="Land area (katha)"
                required
                :error="lead.error('land_size_katha')"
            >
                <template #default="{ id, describedby, invalid }">
                    <Input
                        :id="id"
                        v-model="form.land_size_katha"
                        name="land_size_katha"
                        inputmode="decimal"
                        placeholder="e.g. 5.5"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                        @blur="lead.onBlur('land_size_katha')"
                        @input="lead.onChange('land_size_katha')"
                    />
                </template>
            </FormField>
            <FormField
                :id="lead.fieldId('road_width_ft')"
                label="Road width (ft)"
                :error="lead.error('road_width_ft')"
            >
                <template #default="{ id, describedby, invalid }">
                    <Input
                        :id="id"
                        v-model="form.road_width_ft"
                        name="road_width_ft"
                        inputmode="numeric"
                        placeholder="e.g. 20"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                        @blur="lead.onBlur('road_width_ft')"
                        @input="lead.onChange('road_width_ft')"
                    />
                </template>
            </FormField>
            <FormField
                :id="lead.fieldId('plot_facing')"
                label="Plot facing"
                :error="lead.error('plot_facing')"
            >
                <template #default="{ id, describedby, invalid }">
                    <SelectField
                        :id="id"
                        v-model="form.plot_facing"
                        :options="forms.choices.facing"
                        placeholder="Choose"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </template>
            </FormField>
            <FormField
                :id="lead.fieldId('ownership')"
                label="Ownership"
                :error="lead.error('ownership')"
            >
                <template #default="{ id, describedby, invalid }">
                    <SelectField
                        :id="id"
                        v-model="form.ownership"
                        :options="forms.choices.ownership"
                        placeholder="Choose"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </template>
            </FormField>
        </div>

        <FormField
            :id="lead.fieldId('documents')"
            label="Documents"
            helper="Deeds, mutation or plot papers help us respond faster."
            :error="lead.error('documents')"
        >
            <template #default="{ id, describedby, invalid }">
                <FileUpload
                    :id="id"
                    v-model="form.documents"
                    :accept="files.mimes"
                    :max="files.max"
                    :max-mb="files.max_mb"
                    :types-label="files.label"
                    :invalid="invalid"
                    :describedby="describedby"
                    @rejected="onRejected"
                    @change="form.clearErrors('documents')"
                />
            </template>
        </FormField>

        <MessageField label="Anything else we should know?" />

        <FormFooter submit-label="Send land details" />
    </form>
</template>
