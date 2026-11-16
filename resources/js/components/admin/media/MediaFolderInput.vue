<script setup lang="ts">
import { onMounted, ref, useId, watch } from 'vue';
import { Input } from '@/components/ui/input';

// Inline folder name input: Enter saves, Escape cancels, blur saves (CMS-MED-002, VAL-03 limit 60).
const props = defineProps<{ label: string; error?: string; busy?: boolean }>();

const model = defineModel<string>({ required: true });
const emit = defineEmits<{ submit: []; cancel: [] }>();

const id = useId();
const input = ref<InstanceType<typeof Input> | null>(null);

// Enter / Escape settle the edit; the blur that follows (the input unmounts) must not save again.
let settled = false;

watch([model, () => props.error], () => {
    settled = false;
});

function settle(event: 'submit' | 'cancel'): void {
    if (settled) {
        return;
    }

    settled = true;

    if (event === 'submit') {
        emit('submit');
    } else {
        emit('cancel');
    }
}

function focus(): void {
    const element = (input.value?.$el as HTMLInputElement | undefined) ?? null;

    element?.focus();
    element?.select();
}

onMounted(focus);
defineExpose({ focus });
</script>

<template>
    <div class="grid gap-1">
        <label :for="id" class="sr-only">{{ label }}</label>
        <Input
            :id="id"
            ref="input"
            v-model="model"
            class="h-8 text-sm"
            maxlength="60"
            autocomplete="off"
            :disabled="busy"
            :aria-invalid="!!error || undefined"
            :aria-describedby="error ? `${id}-error` : undefined"
            @keydown.enter.prevent="settle('submit')"
            @keydown.escape.prevent="settle('cancel')"
            @blur="settle('submit')"
        />
        <p v-if="error" :id="`${id}-error`" class="text-xs text-destructive">
            {{ error }}
        </p>
    </div>
</template>
