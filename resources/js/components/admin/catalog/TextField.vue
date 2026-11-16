<script setup lang="ts">
import { computed } from 'vue';
import FormField from '@/components/forms/FormField.vue';
import { Input } from '@/components/ui/input';

// A labelled text or number input for the catalogue editors (CMP-FORM-01): label, required marker,
// helper (HELP-03), the character counter from 80 % of the limit (VAL-03) and the server's error.
// Number inputs send `null` when empty.
const props = defineProps<{
    id: string;
    label: string;
    required?: boolean;
    helper?: string;
    error?: string;
    maxlength?: number;
    numeric?: boolean;
    min?: number;
    max?: number;
    step?: number | string;
    placeholder?: string;
    type?: string;
    inputmode?: 'text' | 'numeric' | 'decimal' | 'tel' | 'url' | 'search';
    autocomplete?: string;
    mono?: boolean;
    disabled?: boolean;
}>();

const model = defineModel<string | number | null>({ default: '' });

const text = computed({
    get: () => (model.value === null ? '' : model.value),
    set: (value: string | number) => {
        model.value = props.numeric && value === '' ? null : value;
    },
});
</script>

<template>
    <FormField
        :id="id"
        :label="label"
        :required="required"
        :helper="helper"
        :error="error"
        :counter="
            maxlength
                ? { length: String(text).length, max: maxlength }
                : undefined
        "
    >
        <template #default="{ describedby, invalid }">
            <Input
                :id="id"
                v-model="text"
                :type="numeric ? 'number' : (type ?? 'text')"
                :maxlength="maxlength"
                :min="min"
                :max="max"
                :step="step"
                :placeholder="placeholder"
                :inputmode="inputmode ?? (numeric ? 'decimal' : undefined)"
                :autocomplete="autocomplete ?? 'off'"
                :disabled="disabled"
                :class="mono ? 'font-mono text-[13px]' : undefined"
                :aria-invalid="invalid || undefined"
                :aria-describedby="describedby"
            />
        </template>
    </FormField>
</template>
