<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import { Textarea } from '@/components/ui/textarea';
import { useLeadFormContext } from '@/composables/useLeadForm';
import type { SitePageProps } from '@/types/site';

// The free-text message (≤ 2000 characters, counter from 80 %); the contact
// form requires at least 20 (FR-LEAD contact row).
const props = withDefaults(
    defineProps<{
        label?: string;
        required?: boolean;
        helper?: string;
        placeholder?: string;
    }>(),
    { label: 'Message' },
);

const lead = useLeadFormContext();
const form = lead.form;
const limits = usePage<SitePageProps>().props.forms.limits;

const length = computed(() => String(form.message ?? '').length);
</script>

<template>
    <FormField
        :id="lead.fieldId('message')"
        :label="props.label"
        :required="props.required"
        :helper="props.helper"
        :error="lead.error('message')"
        :counter="{ length, max: limits.message_max }"
    >
        <template #default="{ id, describedby, invalid }">
            <Textarea
                :id="id"
                v-model="form.message"
                name="message"
                :maxlength="limits.message_max"
                :placeholder="props.placeholder"
                :aria-invalid="invalid || undefined"
                :aria-describedby="describedby"
                @blur="lead.onBlur('message')"
                @input="lead.onChange('message')"
            />
        </template>
    </FormField>
</template>
