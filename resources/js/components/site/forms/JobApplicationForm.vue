<script setup lang="ts">
import type { FormDataConvertible } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, useTemplateRef } from 'vue';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FileUpload from '@/components/forms/FileUpload.vue';
import FormField from '@/components/forms/FormField.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
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
import type { Rule } from '@/lib/forms/rules';
import { notify } from '@/lib/toast';
import { apply } from '@/routes/site/careers';
import type { FormLocation } from '@/types/forms';
import type { SitePageProps } from '@/types/site';

// FR-CAR-002 apply form (PG-16) and the general CV drop of PG-15 (FR-CAR-004,
// `job` null): name*, phone*, email*, current position, years of experience
// (0–50), expected salary, LinkedIn URL, CV* (one PDF/DOC/DOCX ≤ 5 MB),
// cover letter (≤ 3000), consent*. Not a lead: on success the form is
// replaced by an inline confirmation and a toast (owner 8.2), and no
// `generate_lead` is pushed.
const props = defineProps<{
    /** The open role; null for the general CV drop. */
    job: string | null;
    location: FormLocation;
    successTitle: string;
    successText: string;
    successToast: string;
}>();

const forms = usePage<SitePageProps>().props.forms;
const limits = forms.limits;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;

const integer =
    (message: string): Rule =>
    (value) =>
        String(value ?? '').trim() === '' || /^\d+$/.test(String(value).trim())
            ? null
            : message;

const linkedin: Rule = (value) => {
    const text = String(value ?? '').trim();

    if (text === '') {
        return null;
    }

    const message =
        'Enter your LinkedIn profile link, e.g. https://www.linkedin.com/in/your-name.';

    try {
        // The server adds a missing `https://` the same way.
        const url = new URL(
            /^[a-z][a-z\d+.-]*:\/\//i.test(text) ? text : `https://${text}`,
        );
        const host = url.hostname.toLowerCase();

        return /^https?:$/.test(url.protocol) &&
            (host === 'linkedin.com' || host.endsWith('.linkedin.com'))
            ? null
            : message;
    } catch {
        return message;
    }
};

const lead = useLeadForm({
    slug: 'job-application',
    location: props.location,
    url: apply.url(),
    initial: {
        name: '',
        phone_country: forms.default_country,
        phone: '',
        email: '',
        current_position: '',
        years_experience: '',
        expected_salary: '',
        linkedin_url: '',
        cv: [] as File[],
        cover_letter: '',
        consent: false,
    },
    // The CV field holds a list (FileUpload); the server takes one file.
    extra: (): Record<string, FormDataConvertible> => ({
        job: props.job ?? '',
        cv: lead.form.cv[0] ?? null,
    }),
    rules: {
        name: [
            required('Enter your full name.'),
            minLength(2, 'Your name needs at least 2 letters.'),
        ],
        phone: [required('Enter your phone number.'), phone(dial)],
        email: [required('Enter your email address.'), email()],
        years_experience: [
            integer('Enter your experience in whole years, e.g. 3.'),
            numberBetween(0, limits.years_experience_max, {
                number: 'Enter your experience in whole years, e.g. 3.',
                range: `Enter between 0 and ${limits.years_experience_max} years.`,
            }),
        ],
        linkedin_url: [linkedin],
        cv: [required('Attach your CV.')],
        cover_letter: [
            maxLength(
                limits.cover_letter_max,
                `Your cover letter can be up to ${limits.cover_letter_max} characters.`,
            ),
        ],
        consent: [accepted()],
    },
});

const { form, summary, showSummary, summaryItems } = lead;

const done = ref(false);
const status = useTemplateRef<HTMLElement>('status');
const letterLength = computed(() => String(form.cover_letter ?? '').length);

function submit(): void {
    lead.submit({
        onSuccess: async () => {
            done.value = true;
            notify.success(props.successToast);
            await nextTick();
            status.value?.focus();
        },
    });
}

/** Refused files never reach the form; the reason shows under the field (AC-CAR-01). */
function onRejected(message: string): void {
    form.setError('cv', message);
}
</script>

<template>
    <div
        v-if="done"
        ref="status"
        tabindex="-1"
        role="status"
        class="flex flex-col gap-2 outline-none"
    >
        <p class="font-display text-h3 text-balance">{{ successTitle }}</p>
        <p class="max-w-prose text-muted-foreground">{{ successText }}</p>
    </div>

    <form
        v-else
        class="flex flex-col gap-5"
        novalidate
        @submit.prevent="submit"
    >
        <ErrorSummary v-if="showSummary" ref="summary" :items="summaryItems" />

        <PersonFields email="required" />

        <div class="grid gap-5 sm:grid-cols-2">
            <FormField
                :id="lead.fieldId('current_position')"
                label="Current position"
                :error="lead.error('current_position')"
            >
                <template #default="{ id, describedby, invalid }">
                    <Input
                        :id="id"
                        v-model="form.current_position"
                        name="current_position"
                        autocomplete="organization-title"
                        maxlength="120"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                        @blur="lead.onBlur('current_position')"
                        @input="lead.onChange('current_position')"
                    />
                </template>
            </FormField>
            <FormField
                :id="lead.fieldId('years_experience')"
                label="Years of experience"
                :error="lead.error('years_experience')"
            >
                <template #default="{ id, describedby, invalid }">
                    <Input
                        :id="id"
                        v-model="form.years_experience"
                        name="years_experience"
                        inputmode="numeric"
                        placeholder="e.g. 3"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                        @blur="lead.onBlur('years_experience')"
                        @input="lead.onChange('years_experience')"
                    />
                </template>
            </FormField>
            <FormField
                :id="lead.fieldId('expected_salary')"
                label="Expected salary"
                helper="Monthly, in taka."
                :error="lead.error('expected_salary')"
            >
                <template #default="{ id, describedby, invalid }">
                    <Input
                        :id="id"
                        v-model="form.expected_salary"
                        name="expected_salary"
                        maxlength="40"
                        placeholder="e.g. 60,000"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                        @blur="lead.onBlur('expected_salary')"
                        @input="lead.onChange('expected_salary')"
                    />
                </template>
            </FormField>
            <FormField
                :id="lead.fieldId('linkedin_url')"
                label="LinkedIn profile"
                :error="lead.error('linkedin_url')"
            >
                <template #default="{ id, describedby, invalid }">
                    <Input
                        :id="id"
                        v-model="form.linkedin_url"
                        name="linkedin_url"
                        type="url"
                        inputmode="url"
                        maxlength="255"
                        placeholder="linkedin.com/in/your-name"
                        :aria-invalid="invalid || undefined"
                        :aria-describedby="describedby"
                        @blur="lead.onBlur('linkedin_url')"
                        @input="lead.onChange('linkedin_url')"
                    />
                </template>
            </FormField>
        </div>

        <FormField
            :id="lead.fieldId('cv')"
            label="CV"
            required
            :error="lead.error('cv')"
        >
            <template #default="{ id, describedby, invalid }">
                <FileUpload
                    :id="id"
                    v-model="form.cv"
                    :accept="limits.cv.mimes"
                    :extensions="limits.cv.extensions"
                    :max="1"
                    :max-mb="limits.cv.max_mb"
                    :types-label="limits.cv.label"
                    :size-message="`CV must be ${limits.cv.max_mb} MB or smaller.`"
                    type-message="Attach your CV as a PDF, DOC or DOCX file."
                    :invalid="invalid"
                    :describedby="describedby"
                    @rejected="onRejected"
                    @change="form.clearErrors('cv')"
                />
            </template>
        </FormField>

        <FormField
            :id="lead.fieldId('cover_letter')"
            label="Cover letter"
            helper="A few lines on why this role suits you."
            :error="lead.error('cover_letter')"
            :counter="{ length: letterLength, max: limits.cover_letter_max }"
        >
            <template #default="{ id, describedby, invalid }">
                <Textarea
                    :id="id"
                    v-model="form.cover_letter"
                    name="cover_letter"
                    rows="6"
                    :maxlength="limits.cover_letter_max"
                    :aria-invalid="invalid || undefined"
                    :aria-describedby="describedby"
                    @blur="lead.onBlur('cover_letter')"
                    @input="lead.onChange('cover_letter')"
                />
            </template>
        </FormField>

        <FormFooter :submit-label="job ? 'Send application' : 'Send my CV'" />
    </form>
</template>
