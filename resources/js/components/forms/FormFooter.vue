<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';
import ConsentField from '@/components/forms/ConsentField.vue';
import { Button } from '@/components/ui/button';
import { useLeadFormContext } from '@/composables/useLeadForm';
import type { SitePageProps } from '@/types/site';

// Consent (FR-LEAD-014) and the submit row shared by the lead forms. The
// submit button is teal: coral stays the one invitation per view (DP-04).
// The `actions` slot holds buttons that sit beside submit (Back).
defineProps<{
    submitLabel: string;
    busyLabel?: string;
}>();

const lead = useLeadFormContext();
const forms = usePage<SitePageProps>().props.forms;
</script>

<template>
    <ConsentField
        :id="lead.fieldId('consent')"
        v-model="lead.form.consent"
        :text="forms.consent[lead.slug.replaceAll('-', '_')]"
        :privacy-url="forms.privacy_url"
        :error="lead.error('consent')"
        @change="lead.onChange('consent')"
    />
    <div class="flex flex-wrap items-center gap-3">
        <slot name="actions" />
        <Button type="submit" size="lg" :disabled="lead.form.processing">
            <LoaderCircle
                v-if="lead.form.processing"
                class="animate-spin"
                aria-hidden="true"
            />
            {{ lead.form.processing ? (busyLabel ?? 'Sending…') : submitLabel }}
        </Button>
    </div>
</template>
