<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';

// A yes / no setting with its explanation: label and helper on the left, the switch on the right.
defineProps<{
    id: string;
    label: string;
    helper?: string;
    error?: string;
    disabled?: boolean;
}>();

const model = defineModel<boolean>({ default: false });
</script>

<template>
    <div class="grid gap-1">
        <div class="flex items-start justify-between gap-4">
            <div class="grid gap-1">
                <Label :for="id">{{ label }}</Label>
                <p
                    v-if="helper"
                    :id="`${id}-help`"
                    class="text-sm text-muted-foreground"
                >
                    {{ helper }}
                </p>
            </div>
            <Switch
                :id="id"
                v-model="model"
                :disabled="disabled"
                :aria-describedby="helper ? `${id}-help` : undefined"
            />
        </div>
        <p v-if="error" class="text-sm text-destructive">{{ error }}</p>
    </div>
</template>
