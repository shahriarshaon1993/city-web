<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import FormField from '@/components/forms/FormField.vue';
import FormFooter from '@/components/forms/FormFooter.vue';
import MessageField from '@/components/forms/MessageField.vue';
import PersonFields from '@/components/forms/PersonFields.vue';
import RadioCardGroup from '@/components/forms/RadioCardGroup.vue';
import SelectField from '@/components/forms/SelectField.vue';
import { Button } from '@/components/ui/button';
import { useLeadForm } from '@/composables/useLeadForm';
import {
    accepted,
    email,
    maxLength,
    minLength,
    phone,
    required,
} from '@/lib/forms/rules';
import { cn } from '@/lib/utils';
import type { FormProject } from '@/types/forms';
import type { SitePageProps } from '@/types/site';

// `project_inquiry` (CMP-FORM-02, FR-LEAD): step 1 = three radio-card groups
// (timeline, purpose, contact time), all optional with a "Skip" link; step 2
// = name*, phone*, WhatsApp, email, unit type, message, consent*. A step
// change moves focus to the step heading and is announced (aria-live).
const props = defineProps<{
    project: FormProject;
}>();

const forms = usePage<SitePageProps>().props.forms;
const dial = (country: string) =>
    forms.countries.find((item) => item.code === country)?.dial;

const lead = useLeadForm({
    slug: 'project-inquiry',
    location: 'enquiry_dialog',
    initial: {
        timeline: '',
        purpose: '',
        contact_time: '',
        name: '',
        phone_country: forms.default_country,
        phone: '',
        whatsapp_same: false,
        email: '',
        unit_type: '',
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

const STEPS = [
    { title: 'Tell us about your plans', status: 'Step 1 of 2: your plans' },
    { title: 'How can we reach you?', status: 'Step 2 of 2: your details' },
];

const step = ref(1);
const heading = useTemplateRef<HTMLElement>('heading');
const announcement = ref('');

const unitOptions = computed(() =>
    (props.project.unit_types ?? []).map((unit) => ({
        value: String(unit.id),
        label: unit.name,
    })),
);

watch(step, async (next) => {
    announcement.value = STEPS[next - 1].status;
    await nextTick();
    heading.value?.focus();
});

function onSubmit(): void {
    if (step.value === 1) {
        step.value = 2;

        return;
    }

    lead.submit();
}
</script>

<template>
    <form class="flex flex-col gap-6" novalidate @submit.prevent="onSubmit">
        <div class="flex flex-col gap-3">
            <p class="text-overline text-muted-foreground uppercase">
                Step {{ step }} of 2
            </p>
            <div class="grid grid-cols-2 gap-1.5" aria-hidden="true">
                <span
                    v-for="index in 2"
                    :key="index"
                    :class="
                        cn(
                            'h-0.5 rounded-full transition-colors duration-(--dur-base)',
                            index <= step ? 'bg-primary' : 'bg-border',
                        )
                    "
                />
            </div>
            <h3
                ref="heading"
                tabindex="-1"
                class="font-display text-h4 outline-none"
            >
                {{ STEPS[step - 1].title }}
            </h3>
            <p class="sr-only" role="status" aria-live="polite">
                {{ announcement }}
            </p>
        </div>

        <template v-if="step === 1">
            <FormField
                :id="lead.fieldId('timeline')"
                label="When are you planning to buy?"
                group
            >
                <RadioCardGroup
                    v-model="form.timeline"
                    :options="forms.choices.timeline"
                />
            </FormField>
            <FormField
                :id="lead.fieldId('purpose')"
                label="What is it for?"
                group
            >
                <RadioCardGroup
                    v-model="form.purpose"
                    :options="forms.choices.purpose"
                    :columns="3"
                />
            </FormField>
            <FormField
                :id="lead.fieldId('contact_time')"
                label="When can we reach you?"
                group
            >
                <RadioCardGroup
                    v-model="form.contact_time"
                    :options="forms.choices.contact_time"
                    :columns="3"
                />
            </FormField>

            <div class="flex flex-wrap items-center gap-4">
                <Button type="submit" size="lg">Continue</Button>
                <Button type="button" variant="link" @click="step = 2">
                    Skip this step
                </Button>
            </div>
        </template>

        <template v-else>
            <ErrorSummary
                v-if="showSummary"
                ref="summary"
                :items="summaryItems"
            />

            <PersonFields whatsapp />

            <FormField
                v-if="unitOptions.length"
                :id="lead.fieldId('unit_type')"
                label="Unit type you are interested in"
                :error="lead.error('unit_type')"
            >
                <template #default="{ id, describedby, invalid }">
                    <SelectField
                        :id="id"
                        v-model="form.unit_type"
                        :options="unitOptions"
                        placeholder="Any unit type"
                        :invalid="invalid"
                        :describedby="describedby"
                    />
                </template>
            </FormField>

            <MessageField />

            <FormFooter submit-label="Send enquiry">
                <template #actions>
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        @click="step = 1"
                    >
                        Back
                    </Button>
                </template>
            </FormFooter>
        </template>
    </form>
</template>
