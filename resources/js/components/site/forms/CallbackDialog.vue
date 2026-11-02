<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import FormDialog from '@/components/forms/FormDialog.vue';
import CallbackForm from '@/components/site/forms/CallbackForm.vue';
import { useCallbackDialog } from '@/composables/useCallbackDialog';
import type { SitePageProps } from '@/types/site';

// "Call me back" (FR-LEAD callback), mounted once in the public layout and
// opened from the footer. Copy from Settings → Forms.
const { isOpen, opener } = useCallbackDialog();
const copy = usePage<SitePageProps>().props.forms.callback_dialog;
</script>

<template>
    <FormDialog
        v-model:open="isOpen"
        :title="copy.title"
        :description="copy.text"
        :return-focus="opener"
    >
        <CallbackForm location="footer" />
    </FormDialog>
</template>
