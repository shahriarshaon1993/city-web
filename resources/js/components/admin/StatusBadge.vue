<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';

// CMP-ADM-STATUS: soft badge with a dot ("● Signed in"). The text always carries the meaning;
// the tone only supports it.
const props = defineProps<{
    label: string;
    tone: 'success' | 'info' | 'warning' | 'error' | 'neutral' | string;
}>();

const classes = computed(
    () =>
        ({
            success: 'bg-success-50 text-success-700',
            info: 'bg-info-50 text-info-700',
            warning: 'bg-warning-50 text-warning-700',
            error: 'bg-error-50 text-error-700',
        })[props.tone] ?? 'bg-stone-100 text-stone-700',
);

const dot = computed(
    () =>
        ({
            success: 'bg-success-500',
            info: 'bg-info-500',
            warning: 'bg-warning-500',
            error: 'bg-error-500',
        })[props.tone] ?? 'bg-stone-400',
);
</script>

<template>
    <span
        :class="
            cn(
                'inline-flex h-[22px] items-center gap-1.5 rounded-full px-2.5 text-xs font-medium whitespace-nowrap',
                classes,
            )
        "
    >
        <span :class="cn('size-1.5 rounded-full', dot)" aria-hidden="true" />
        {{ label }}
    </span>
</template>
