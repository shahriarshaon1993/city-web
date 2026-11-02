<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FormField from '@/components/forms/FormField.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import MessageField from '@/components/forms/MessageField.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
import RadioCardGroup from '@/components/forms/RadioCardGroup.vue';
import SelectField from '@/components/forms/SelectField.vue';
import { useLeadForm } from '@/composables/useLeadForm';
import {
    accepted,
    email,
    maxLength,
    minLength,
    phone,
    required,
} from '@/lib/forms/rules';
import type { FormLocation } from '@/types/forms';
import type { SitePageProps } from '@/types/site';

// `nrb` (PG-11, FR-LEAD): name*, country of residence*, phone with country
// code*, WhatsApp, email*, contact time in Bangladesh time*, budget band,
// message, consent*.
const props = defineProps<{
    location: FormLocation;
}>();

const forms = usePage<SitePageProps>().props.forms;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;

const lead = useLeadForm({
    slug: 'nrb',
    location: props.location,
    initial: {
        name: '',
        country: '',
        phone_country: forms.default_country,
        phone: '',
        whatsapp_same: false,
        email: '',
        contact_time: '',
        budget_band: '',
        message: '',
        consent: false,
    },
    rules: {
        name: [
            required('Enter your full name.'),
            minLength(2, 'Your name needs at least 2 letters.'),
        ],
        country: [required('Choose your country of residence.')],
        phone: [required('Enter your phone number.'), phone(dial)],
        email: [required('Enter your email address.'), email()],
        contact_time: [required('Choose a time for our call.')],
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

const countryOptions = forms.countries.map((country) => ({
    value: country.code,
    label: country.name,
}));

// Choosing the country of residence also picks the phone's country code,
// until the visitor has started typing a number.
watch(
    () => form.country,
    (code) => {
        if (code !== '' && form.phone === '') {
            form.phone_country = code;
        }
    },
);
</script>

<template>
    <form
        class="flex flex-col gap-5"
        novalidate
        @submit.prevent="lead.submit()"
    >
        <ErrorSummary v-if="showSummary" ref="summary" :items="summaryItems" />

        <FormField
            :id="lead.fieldId('country')"
            label="Country of residence"
            required
            :error="lead.error('country')"
        >
            <template #default="{ id, describedby, invalid }">
                <SelectField
                    :id="id"
                    v-model="form.country"
                    :options="countryOptions"
                    placeholder="Choose your country"
                    :invalid="invalid"
                    :describedby="describedby"
                    @blur="lead.onBlur('country')"
                    @change="lead.onChange('country')"
                />
            </template>
        </FormField>

        <PersonFields email="required" whatsapp />

        <FormField
            :id="lead.fieldId('contact_time')"
            label="Best time to call (Bangladesh time)"
            group
            required
            :error="lead.error('contact_time')"
        >
            <RadioCardGroup
                v-model="form.contact_time"
                :options="forms.choices.contact_time"
                :columns="3"
                :invalid="!!lead.error('contact_time')"
                @change="lead.onChange('contact_time')"
            />
        </FormField>

        <FormField
            :id="lead.fieldId('budget_band')"
            label="Budget"
            helper="Optional. It helps us suggest the right projects."
            :error="lead.error('budget_band')"
        >
            <template #default="{ id, describedby, invalid }">
                <SelectField
                    :id="id"
                    v-model="form.budget_band"
                    :options="forms.choices.budget"
                    placeholder="Choose a range"
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </template>
        </FormField>

        <MessageField />

        <FormFooter submit-label="Send my details" />
    </form>
</template>
