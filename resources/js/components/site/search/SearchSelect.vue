<script setup lang="ts">
import { ChevronDown } from '@lucide/vue';
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import ClientOnly from '@/components/site/ClientOnly.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

// HERO-042 (DEC-42): server HTML and no-JS visitors get a native <select>;
// after hydration it becomes the Reka Select with the same look. With JS the
// widget builds the URL itself; the hidden input keeps the form submittable.
const props = defineProps<{
    id: string;
    name: string;
    label: string;
    /** Shown while nothing is chosen. */
    placeholder: string;
    /** The list item that clears the choice ("Any status"). */
    anyLabel: string;
    options: { value: string; label: string }[];
    labelClass?: HTMLAttributes['class'];
    controlClass?: HTMLAttributes['class'];
    /** z-index layer of the popover (inside a sheet it must sit above it). */
    contentClass?: HTMLAttributes['class'];
}>();

const model = defineModel<string>({ required: true });

// Reka items cannot have an empty value.
const ANY = '__any';

const selectModel = computed({
    get: () => model.value || ANY,
    set: (value: string) => {
        model.value = value === ANY ? '' : value;
    },
});

const display = computed(
    () =>
        props.options.find((option) => option.value === model.value)?.label ??
        props.placeholder,
);

const chevron =
    'pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-muted-foreground';
</script>

<template>
    <div class="min-w-0">
        <label :for="id" :class="labelClass">{{ label }}</label>
        <ClientOnly>
            <Select v-model="selectModel">
                <SelectTrigger
                    :id="id"
                    :class="
                        cn(
                            controlClass,
                            !model &&
                                '[&>[data-slot=select-value]]:text-muted-foreground',
                        )
                    "
                >
                    <SelectValue>{{ display }}</SelectValue>
                </SelectTrigger>
                <SelectContent data-lenis-prevent :class="contentClass">
                    <SelectItem :value="ANY">{{ anyLabel }}</SelectItem>
                    <SelectItem
                        v-for="option in options"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <input type="hidden" :name="name" :value="model" />

            <template #fallback>
                <span class="relative block">
                    <select
                        :id="id"
                        v-model="model"
                        :name="name"
                        :class="
                            cn(
                                controlClass,
                                'appearance-none pr-10',
                                !model && 'text-muted-foreground',
                            )
                        "
                    >
                        <option value="">{{ placeholder }}</option>
                        <option
                            v-for="option in options"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <ChevronDown :class="chevron" aria-hidden="true" />
                </span>
            </template>
        </ClientOnly>
    </div>
</template>
