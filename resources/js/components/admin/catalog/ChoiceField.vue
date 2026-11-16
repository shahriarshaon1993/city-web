<script setup lang="ts" generic="T extends string | number">
import FormField from '@/components/forms/FormField.vue';
import AdminSelect from './AdminSelect.vue';

// A labelled select for the catalogue editors (CMP-FORM-01): see AdminSelect for groups and "None".
defineProps<{
    id: string;
    label: string;
    options: Array<{ value: T; label: string; group?: string }>;
    required?: boolean;
    helper?: string;
    error?: string;
    placeholder?: string;
    noneLabel?: string;
    disabled?: boolean;
}>();

const model = defineModel<T | null | ''>({ default: null });
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
            <AdminSelect
                :id="id"
                v-model="model"
                :options="options"
                :placeholder="placeholder"
                :none-label="noneLabel"
                :disabled="disabled"
                :invalid="invalid"
                :describedby="describedby"
            />
        </template>
    </FormField>
</template>
