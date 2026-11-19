<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { DashboardRange } from '@/types/admin-dashboard';

// 12.2 widget filter: 7 / 30 / 90 days, a real button group with aria-pressed.
defineProps<{ modelValue: DashboardRange; label: string }>();
defineEmits<{ 'update:modelValue': [value: DashboardRange] }>();

const options: DashboardRange[] = [7, 30, 90];
</script>

<template>
    <div
        role="group"
        :aria-label="label"
        class="inline-flex rounded-md border p-0.5"
    >
        <button
            v-for="option in options"
            :key="option"
            type="button"
            :aria-pressed="modelValue === option"
            :class="
                cn(
                    'min-h-8 min-w-11 rounded-[5px] px-2 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    modelValue === option && 'bg-muted text-foreground',
                )
            "
            @click="$emit('update:modelValue', option)"
        >
            {{ option }} days
        </button>
    </div>
</template>
