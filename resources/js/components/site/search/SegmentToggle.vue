<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

// CMP-SEARCH-01 segmented control as native radios: one exclusive choice,
// arrow keys move between options and it works without JavaScript (HERO-042).
defineProps<{
    name: string;
    legend: string;
    options: { value: string; label: string }[];
    legendClass?: HTMLAttributes['class'];
}>();

const model = defineModel<string>({ required: true });
</script>

<template>
    <fieldset class="min-w-0">
        <legend :class="legendClass">{{ legend }}</legend>
        <div class="flex rounded-md bg-muted p-0.5">
            <label
                v-for="option in options"
                :key="option.value"
                class="relative flex min-h-11 flex-1 cursor-pointer items-center justify-center rounded-sm px-3 text-sm font-medium whitespace-nowrap text-muted-foreground transition-colors duration-(--dur-fast) hover:text-foreground has-checked:bg-background has-checked:text-foreground has-checked:shadow-xs has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-ring"
            >
                <input
                    v-model="model"
                    type="radio"
                    :name="name"
                    :value="option.value"
                    class="sr-only"
                />
                {{ option.label }}
            </label>
        </div>
    </fieldset>
</template>
