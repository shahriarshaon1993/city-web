<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import DatePicker from '@/components/forms/DatePicker.vue';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FormField from '@/components/forms/FormField.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import MessageField from '@/components/forms/MessageField.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
import RadioCardGroup from '@/components/forms/RadioCardGroup.vue';
import SelectField from '@/components/forms/SelectField.vue';
import { useLeadForm } from '@/composables/useLeadForm';
import { formatDate } from '@/lib/format';
import {
    accepted,
    dateWithin,
    maxLength,
    minLength,
    phone,
    required,
} from '@/lib/forms/rules';
import type { FormProject } from '@/types/forms';
import type { SitePageProps } from '@/types/site';

// `site_visit` (PG-04 "Book a site visit", FR-LEAD): name*, phone*,
// preferred date* (tomorrow … +60 days, Fridays allowed), time slot*,
// visitors (1–10), message, consent*. The project is a hidden field.
const props = defineProps<{
    project: FormProject;
}>();

const forms = usePage<SitePageProps>().props.forms;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;
const visitWindow = forms.visit_window;

const visitorOptions = Array.from(
    { length: forms.limits.visitors.max },
    (_, index) => ({
        value: String(index + forms.limits.visitors.min),
        label:
            index + forms.limits.visitors.min === 1
                ? '1 visitor'
                : `${index + forms.limits.visitors.min} visitors`,
    }),
);

const lead = useLeadForm({
    slug: 'site-visit',
    location: 'visit_dialog',
    initial: {
        name: '',
        phone_country: forms.default_country,
        phone: '',
        visit_date: '',
        visit_slot: '',
        visitors: '1',
        message: '',
        consent: false,
    },
    rules: {
        name: [
            required('Enter your full name.'),
            minLength(2, 'Your name needs at least 2 letters.'),
        ],
        phone: [required('Enter your phone number.'), phone(dial)],
        visit_date: [
            required('Choose a date for your visit.'),
            dateWithin(
                visitWindow.min,
                visitWindow.max,
                `Choose a date between ${formatDate(visitWindow.min)} and ${formatDate(visitWindow.max)}.`,
            ),
        ],
        visit_slot: [required('Choose a time of day for your visit.')],
        message: [
            maxLength(
                forms.limits.message_max,
                `Your message can be up to ${forms.limits.message_max} characters.`,
            ),
        ],
        consent: [accepted()],
    },
    extra: () => ({ project: props.project.slug }),
});

const { form, summary, showSummary, summaryItems } = lead;
</script>

<template>
    <form
        class="flex flex-col gap-5"
        novalidate
        @submit.prevent="lead.submit()"
    >
        <ErrorSummary v-if="showSummary" ref="summary" :items="summaryItems" />

        <PersonFields email="none" />

        <FormField
            :id="lead.fieldId('visit_date')"
            label="Preferred date"
            required
            :helper="`Any day from ${formatDate(visitWindow.min)} to ${formatDate(visitWindow.max)}, Fridays included.`"
            :error="lead.error('visit_date')"
        >
            <template #default="{ id, describedby, invalid }">
                <DatePicker
                    :id="id"
                    v-model="form.visit_date"
                    :min="visitWindow.min"
                    :max="visitWindow.max"
                    placeholder="Choose a date"
                    :invalid="invalid"
                    :describedby="describedby"
                    @blur="lead.onBlur('visit_date')"
                    @change="lead.onChange('visit_date')"
                />
            </template>
        </FormField>

        <FormField
            :id="lead.fieldId('visit_slot')"
            label="Preferred time"
            group
            required
            :error="lead.error('visit_slot')"
        >
            <RadioCardGroup
                v-model="form.visit_slot"
                :options="forms.choices.contact_time"
                :columns="3"
                :invalid="!!lead.error('visit_slot')"
                @change="lead.onChange('visit_slot')"
            />
        </FormField>

        <FormField
            :id="lead.fieldId('visitors')"
            label="Number of visitors"
            :error="lead.error('visitors')"
        >
            <template #default="{ id, describedby, invalid }">
                <SelectField
                    :id="id"
                    v-model="form.visitors"
                    :options="visitorOptions"
                    placeholder="Choose"
                    :invalid="invalid"
                    :describedby="describedby"
                />
            </template>
        </FormField>

        <MessageField label="Anything we should know?" />

        <FormFooter submit-label="Request site visit" />
    </form>
</template>
