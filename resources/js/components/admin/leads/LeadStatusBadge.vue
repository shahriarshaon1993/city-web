<script setup lang="ts">
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { STATUS_TONE } from '@/lib/leads';
import type { LeadStatusValue } from '@/types';

// A lead's pipeline status (FR-LEAD-006): soft badge, the label carries the meaning.
defineProps<{ status: LeadStatusValue; label: string }>();
</script>

<template>
    <StatusBadge :label="label" :tone="STATUS_TONE[status]" />
</template>
