<script setup lang="ts">
import { computed } from 'vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import { confirmState, settleConfirm } from '@/composables/useConfirm';

// The one confirm dialog behind `useConfirm()`; mounted once in AdminLayout.
const open = computed({
    get: () => confirmState.open,
    set: (value: boolean) => {
        // Closing without confirming (Cancel, Esc, overlay) answers "no".
        if (!value) {
            settleConfirm(null);
        }
    },
});
</script>

<template>
    <ConfirmDialog
        v-if="confirmState.options"
        v-model:open="open"
        :title="confirmState.options.title"
        :description="confirmState.options.description"
        :confirm-label="confirmState.options.confirmLabel"
        :subject="confirmState.options.subject"
        :destructive="confirmState.options.destructive"
        :type-to-confirm="confirmState.options.typeToConfirm"
        :redirect="confirmState.options.redirect"
        @confirm="(redirect) => settleConfirm({ redirect })"
    />
</template>
