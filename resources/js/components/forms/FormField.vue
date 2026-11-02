<script setup lang="ts">
import { CircleAlert } from '@lucide/vue';
import { computed } from 'vue';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

// CMP-FORM-01 anatomy: label (required marker "*" aria-hidden + "(required)"
// for assistive tech), control, helper, error with an icon (A11Y-D10).
// `group` renders a fieldset/legend for radio and checkbox groups. The
// control gets `id` and `describedby` through the slot; `counter` shows the
// character count from 80 % of the limit (VAL-03).
const props = defineProps<{
    id: string;
    label: string;
    required?: boolean;
    helper?: string;
    error?: string;
    group?: boolean;
    counter?: { length: number; max: number };
}>();

const helperId = computed(() => `${props.id}-helper`);
const errorId = computed(() => `${props.id}-error`);

const describedby = computed(
    () =>
        [
            props.helper ? helperId.value : null,
            props.error ? errorId.value : null,
        ]
            .filter(Boolean)
            .join(' ') || undefined,
);

const showCounter = computed(
    () =>
        props.counter !== undefined &&
        props.counter.length >= props.counter.max * 0.8,
);
</script>

<template>
    <component
        :is="group ? 'fieldset' : 'div'"
        :id="group ? id : undefined"
        :aria-describedby="group ? describedby : undefined"
        class="flex min-w-0 flex-col gap-2"
    >
        <legend v-if="group" class="mb-0.5 p-0 text-sm font-medium">
            {{ label }}
            <template v-if="required">
                <span class="text-destructive" aria-hidden="true">*</span>
                <span class="sr-only">(required)</span>
            </template>
        </legend>
        <Label v-else :for="id" :required="required">{{ label }}</Label>

        <slot :id="id" :describedby="describedby" :invalid="!!error" />

        <div
            v-if="helper || showCounter"
            class="flex items-start justify-between gap-3 text-sm text-muted-foreground"
        >
            <p v-if="helper" :id="helperId" class="text-pretty">
                {{ helper }}
            </p>
            <p
                v-if="showCounter && counter"
                :class="
                    cn(
                        'tabular ml-auto shrink-0',
                        counter.length >= counter.max && 'text-destructive',
                    )
                "
            >
                {{ counter.length }} / {{ counter.max }}
            </p>
        </div>
        <p
            v-if="error"
            :id="errorId"
            class="flex items-start gap-1.5 text-sm text-destructive"
        >
            <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <span>{{ error }}</span>
        </p>
    </component>
</template>
