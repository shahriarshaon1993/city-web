<script setup lang="ts">
import FormField from '@/components/forms/FormField.vue';
import MonthPicker from '@/components/forms/MonthPicker.vue';

// A labelled month picker for the catalogue editors (CMP-FORM-01): the model is `YYYY-MM` or "".
defineProps<{
    id: string;
    label: string;
    required?: boolean;
    helper?: string;
    error?: string;
    min?: string;
    max?: string;
    placeholder?: string;
}>();

const model = defineModel<string | null>({ default: '' });
</script>

<template>
    <FormField
        :id="id"
        :label="label"
        :required="required"
        :helper="helper"
        :error="error"
    >
        <template #default="{ describedby, invalid }">
            <MonthPicker
                :id="id"
                v-model="model"
                :min="min"
                :max="max"
                :placeholder="placeholder"
                :clearable="!required"
                :invalid="invalid"
                :describedby="describedby"
            />
        </template>
    </FormField>
</template>
