<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import FormField from '@/components/forms/FormField.vue';
import PhoneInput from '@/components/forms/PhoneInput.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { useLeadFormContext } from '@/composables/useLeadForm';
import type { SitePageProps } from '@/types/site';

// Name, phone (+ country), optional "WhatsApp is the same" and email: the
// contact block every lead form starts its details with. Reads the enclosing
// form from useLeadForm (the form's values must include these keys).
withDefaults(
    defineProps<{
        email?: 'required' | 'optional' | 'none';
        whatsapp?: boolean;
        nameLabel?: string;
    }>(),
    { email: 'optional', nameLabel: 'Full name' },
);

const lead = useLeadFormContext();
const form = lead.form;
const forms = usePage<SitePageProps>().props.forms;
</script>

<template>
    <div class="grid gap-5 sm:grid-cols-2">
        <FormField
            :id="lead.fieldId('name')"
            :label="nameLabel"
            required
            :error="lead.error('name')"
            class="sm:col-span-2"
        >
            <template #default="{ id, describedby, invalid }">
                <Input
                    :id="id"
                    v-model="form.name"
                    name="name"
                    autocomplete="name"
                    maxlength="120"
                    :aria-invalid="invalid || undefined"
                    :aria-describedby="describedby"
                    @blur="lead.onBlur('name')"
                    @input="lead.onChange('name')"
                />
            </template>
        </FormField>

        <FormField
            :id="lead.fieldId('phone')"
            label="Phone number"
            required
            :error="lead.error('phone')"
            :class="
                whatsapp || email !== 'optional'
                    ? 'sm:col-span-2'
                    : 'sm:col-span-1'
            "
        >
            <template #default="{ id, describedby, invalid }">
                <PhoneInput
                    :id="id"
                    v-model:phone="form.phone"
                    v-model:country="form.phone_country"
                    :countries="forms.countries"
                    :invalid="invalid"
                    :describedby="describedby"
                    @blur="lead.onBlur('phone')"
                    @change="lead.onChange('phone')"
                />
            </template>
        </FormField>

        <div v-if="whatsapp" class="-mt-2 sm:col-span-2">
            <div class="flex min-h-11 items-center gap-3">
                <Checkbox
                    :id="lead.fieldId('whatsapp_same')"
                    v-model="form.whatsapp_same"
                />
                <label
                    :for="lead.fieldId('whatsapp_same')"
                    class="text-sm leading-snug"
                >
                    This number is on WhatsApp
                </label>
            </div>
        </div>

        <FormField
            v-if="email !== 'none'"
            :id="lead.fieldId('email')"
            label="Email"
            :required="email === 'required'"
            :error="lead.error('email')"
            :class="
                whatsapp || email !== 'optional'
                    ? 'sm:col-span-2'
                    : 'sm:col-span-1'
            "
        >
            <template #default="{ id, describedby, invalid }">
                <Input
                    :id="id"
                    v-model="form.email"
                    name="email"
                    type="email"
                    inputmode="email"
                    autocomplete="email"
                    maxlength="190"
                    placeholder="name@example.com"
                    :aria-invalid="invalid || undefined"
                    :aria-describedby="describedby"
                    @blur="lead.onBlur('email')"
                    @input="lead.onChange('email')"
                />
            </template>
        </FormField>
    </div>
</template>
