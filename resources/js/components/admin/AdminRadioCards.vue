<script setup lang="ts">
import type { Component } from 'vue';

type Option = {
    value: string;
    label: string;
    description?: string;
    icon?: Component;
};

defineProps<{
    legend: string;
    name: string;
    options: Option[];
    modelValue: string;
}>();

defineEmits<{ 'update:modelValue': [value: string] }>();
</script>

<template>
    <!-- Native radios: arrow keys and the group label come from the browser. -->
    <fieldset>
        <legend class="mb-2 text-sm font-medium">{{ legend }}</legend>
        <div class="grid gap-3 sm:grid-cols-3">
            <label
                v-for="option in options"
                :key="option.value"
                class="relative flex cursor-pointer flex-col gap-1 rounded-lg border bg-background p-4 transition-colors hover:border-foreground/40 has-checked:border-primary has-checked:bg-surface-brand has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ring"
            >
                <input
                    type="radio"
                    class="sr-only"
                    :name="name"
                    :value="option.value"
                    :checked="modelValue === option.value"
                    @change="$emit('update:modelValue', option.value)"
                />
                <component
                    :is="option.icon"
                    v-if="option.icon"
                    class="size-5 text-muted-foreground"
                    aria-hidden="true"
                />
                <span class="text-sm font-medium">{{ option.label }}</span>
                <span
                    v-if="option.description"
                    class="text-xs text-muted-foreground"
                    >{{ option.description }}</span
                >
            </label>
        </div>
    </fieldset>
</template>
