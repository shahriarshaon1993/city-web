<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FormField from '@/components/forms/FormField.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import MessageField from '@/components/forms/MessageField.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
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

// `contact` (PG-17, FR-LEAD): name*, phone*, email, subject*, message*
// (20–2000), consent*. Lives in the form block and on the contact page.
const props = defineProps<{
    location: FormLocation;
    /** Pre-selected subject (e.g. `sales` from a project link). */
    subject?: string;
    project?: string | null;
}>();

const forms = usePage<SitePageProps>().props.forms;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;

const lead = useLeadForm({
    slug: 'contact',
    location: props.location,
    initial: {
        name: '',
        phone_country: forms.default_country,
        phone: '',
        email: '',
        subject: props.subject ?? '',
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
        subject: [required('Choose what your message is about.')],
        message: [
            required('Write a short message, so we know how to help.'),
            minLength(
                forms.limits.contact_message_min,
                `Your message needs at least ${forms.limits.contact_message_min} characters, so we know how to help.`,
            ),
            maxLength(
                forms.limits.message_max,
                `Your message can be up to ${forms.limits.message_max} characters.`,
            ),
        ],
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

        <PersonFields />

        <FormField
            :id="lead.fieldId('subject')"
            label="What is it about?"
            required
            :error="lead.error('subject')"
        >
            <template #default="{ id, describedby, invalid }">
                <SelectField
                    :id="id"
                    v-model="form.subject"
                    :options="forms.choices.subject"
                    placeholder="Choose a subject"
                    :invalid="invalid"
                    :describedby="describedby"
                    @blur="lead.onBlur('subject')"
                    @change="lead.onChange('subject')"
                />
            </template>
        </FormField>

        <MessageField
            required
            :helper="`At least ${forms.limits.contact_message_min} characters.`"
        />

        <FormFooter submit-label="Send message" />
    </form>
</template>
