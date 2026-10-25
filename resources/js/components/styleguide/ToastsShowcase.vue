<script setup lang="ts">
import StyleguideSection from '@/components/styleguide/StyleguideSection.vue';
import { Button } from '@/components/ui/button';
import { notify } from '@/lib/toast';

// CMP-TOAST-01: one trigger per tone; durations come from `notify`.
const samples = [
    {
        label: 'Success (4 s)',
        show: () =>
            notify.success('Enquiry sent', {
                description: 'We will call you within one working day.',
            }),
    },
    { label: 'Info (4 s)', show: () => notify.info('Added to shortlist') },
    { label: 'Warning (6 s)', show: () => notify.warning('Only 3 units left') },
    {
        label: 'Error (stays)',
        show: () =>
            notify.error('Could not send', {
                description: 'Check your connection and try again.',
            }),
    },
    {
        label: 'With action',
        show: () =>
            notify.success('Project archived', {
                action: {
                    label: 'Undo',
                    onClick: () => notify.info('Restored'),
                },
            }),
    },
];
</script>

<template>
    <StyleguideSection id="sg-toasts" title="Toasts">
        <div class="flex flex-wrap gap-3">
            <Button
                v-for="sample in samples"
                :key="sample.label"
                variant="outline"
                @click="sample.show"
            >
                {{ sample.label }}
            </Button>
        </div>
    </StyleguideSection>
</template>
