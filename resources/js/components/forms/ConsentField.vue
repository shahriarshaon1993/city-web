<script setup lang="ts">
import { CircleAlert } from '@lucide/vue';
import { Checkbox } from '@/components/ui/checkbox';

// FR-LEAD-014: the consent text comes from Settings → Forms (`forms.consent`);
// the exact text shown is stored with the lead (15.1). Required, never ticked.
defineProps<{
    id: string;
    text: string;
    privacyUrl: string;
    error?: string;
}>();

const emit = defineEmits<{
    change: [];
}>();

const model = defineModel<boolean>({ default: false });
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex items-start gap-3">
            <Checkbox
                :id="id"
                v-model="model"
                class="mt-0.5"
                :aria-invalid="error ? true : undefined"
                :aria-describedby="error ? `${id}-error` : undefined"
                aria-required="true"
                @update:model-value="emit('change')"
            />
            <label :for="id" class="text-sm leading-snug text-pretty">
                {{ text }}
                <span class="sr-only">(required)</span>
                <a
                    :href="privacyUrl"
                    target="_blank"
                    rel="noopener"
                    class="ml-1 whitespace-nowrap underline underline-offset-4 hover:text-primary"
                >
                    Privacy policy<span class="sr-only">
                        (opens in a new tab)</span
                    >
                </a>
            </label>
        </div>
        <p
            v-if="error"
            :id="`${id}-error`"
            class="flex items-start gap-1.5 text-sm text-destructive"
        >
            <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>{{ error }}</span>
        </p>
    </div>
</template>
