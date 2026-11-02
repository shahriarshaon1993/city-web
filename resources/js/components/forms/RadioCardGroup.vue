<script setup lang="ts">
import { RadioGroupItem, RadioGroupRoot } from 'reka-ui';
import { cn } from '@/lib/utils';
import type { FormOption } from '@/types/forms';

// CMP-FORM-02 radio cards (project enquiry step 1, visit slots, call times):
// hairline boxes that fill with the foreground colour when chosen, like the
// listing's panel choices (direction.md §6). Reka's radio group gives the
// arrow-key roving focus; a fieldset legend (FormField group) names it.
defineProps<{
    options: FormOption[];
    invalid?: boolean;
    /** Phones show the cards in one column unless this is set. */
    columns?: 2 | 3 | 4;
}>();

const emit = defineEmits<{
    change: [];
    blur: [];
}>();

const model = defineModel<string>({ default: '' });
</script>

<template>
    <RadioGroupRoot
        :model-value="model || undefined"
        :class="
            cn(
                'grid gap-2',
                columns === 2 && 'grid-cols-2',
                columns === 3 && 'sm:grid-cols-3',
                columns === 4 && 'grid-cols-2 sm:grid-cols-4',
                !columns && 'sm:grid-cols-2',
            )
        "
        @update:model-value="
            (value) => {
                model = String(value ?? '');
                emit('change');
            }
        "
    >
        <RadioGroupItem
            v-for="option in options"
            :key="option.value"
            :value="option.value"
            :aria-invalid="invalid || undefined"
            class="flex min-h-11 items-center rounded-md border border-foreground/30 bg-background px-4 py-2.5 text-left text-sm transition-[color,background-color,border-color] duration-(--dur-fast) hover:border-foreground data-[state=checked]:border-foreground data-[state=checked]:bg-foreground data-[state=checked]:text-background"
            @blur="emit('blur')"
        >
            {{ option.label }}
        </RadioGroupItem>
    </RadioGroupRoot>
</template>
