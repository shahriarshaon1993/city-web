<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FormField from '@/components/forms/FormField.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
import RadioCardGroup from '@/components/forms/RadioCardGroup.vue';
import { useLeadForm } from '@/composables/useLeadForm';
import { accepted, phone, required, minLength } from '@/lib/forms/rules';
import type { FormLocation } from '@/types/forms';
import type { SitePageProps } from '@/types/site';

// `callback` ("Call me back", FR-LEAD): name*, phone*, preferred time*, consent*.
const props = defineProps<{
    location: FormLocation;
    project?: string | null;
}>();

const forms = usePage<SitePageProps>().props.forms;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;

const lead = useLeadForm({
    slug: 'callback',
    location: props.location,
    initial: {
        name: '',
        phone_country: forms.default_country,
        phone: '',
        contact_time: '',
        consent: false,
    },
    rules: {
        name: [
            required('Enter your full name.'),
            minLength(2, 'Your name needs at least 2 letters.'),
        ],
        phone: [required('Enter your phone number.'), phone(dial)],
        contact_time: [required('Choose a time for our call.')],
        consent: [accepted()],
    },
    extra: () => (props.project ? { project: props.project } : {}),
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
            :id="lead.fieldId('contact_time')"
            label="When should we call?"
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

        <FormFooter submit-label="Request a call back" />
    </form>
</template>
