<script setup lang="ts">
import AdminSection from '@/components/admin/AdminSection.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import { useProjectEditor } from './context';

// Lead routing: who a new enquiry for this project is assigned to, and an own WhatsApp number.
// The assignment itself starts with the Leads module (15.1); the values are stored from now on.
const { form, options, errors } = useProjectEditor();
</script>

<template>
    <AdminSection
        title="Lead routing"
        description="Where enquiries about this project go."
    >
        <div class="grid gap-5">
            <ChoiceField
                id="field-default_assignee_id"
                v-model="form.default_assignee_id"
                label="Default assignee"
                none-label="Nobody — leave unassigned"
                placeholder="Nobody — leave unassigned"
                :options="options.assignees"
                helper="A Sales user who receives new leads for this project. Empty leaves them unassigned for a manager."
                :error="errors.default_assignee_id"
            />
            <TextField
                id="field-whatsapp_override"
                v-model="form.whatsapp_override"
                type="tel"
                inputmode="tel"
                label="WhatsApp number"
                :maxlength="20"
                placeholder="+8801712345678"
                helper="Only when this project has its own sales line. Empty uses the company WhatsApp number. Include +880."
                :error="errors.whatsapp_override"
            />
        </div>
    </AdminSection>
</template>
