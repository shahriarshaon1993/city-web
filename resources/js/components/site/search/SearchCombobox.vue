<script setup lang="ts">
import { Check, ChevronDown } from '@lucide/vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxGroup,
    ComboboxInput,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxLabel,
    ComboboxPortal,
    ComboboxRoot,
    ComboboxTrigger,
    ComboboxViewport,
} from 'reka-ui';
import { computed } from 'vue';
import type { HTMLAttributes } from 'vue';
import ClientOnly from '@/components/site/ClientOnly.vue';
import { cn } from '@/lib/utils';
import type { SearchOption } from '@/types/search';

// HERO-040 Location: options grouped by city. Native <select> with
// <optgroup>s before hydration and without JS (HERO-042, DEC-42); a
// type-to-filter Reka Combobox afterwards.
const props = defineProps<{
    id: string;
    name: string;
    label: string;
    /** Input placeholder while nothing is chosen. */
    placeholder: string;
    /** The list item that clears the choice ("All locations"). */
    anyLabel: string;
    emptyText: string;
    groups: { label: string; options: SearchOption[] }[];
    labelClass?: HTMLAttributes['class'];
    controlClass?: HTMLAttributes['class'];
    contentClass?: HTMLAttributes['class'];
}>();

const model = defineModel<string>({ required: true });

const ANY = '__any';

const comboModel = computed({
    get: () => model.value || ANY,
    set: (value: string) => {
        model.value = value === ANY ? '' : value;
    },
});

const labels = computed(
    () =>
        new Map(
            props.groups.flatMap((group) =>
                group.options.map((option) => [option.value, option.label]),
            ),
        ),
);

function displayValue(value: string): string {
    return value === ANY ? '' : (labels.value.get(value) ?? '');
}

const chevron =
    'pointer-events-none absolute top-1/2 right-3.5 size-4 -translate-y-1/2 text-muted-foreground';

const itemClass =
    'relative flex min-h-10 cursor-default items-center rounded-sm py-2 pr-8 pl-3 text-sm outline-none select-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground';
</script>

<template>
    <div class="min-w-0">
        <label :for="id" :class="labelClass">{{ label }}</label>
        <ClientOnly>
            <ComboboxRoot v-model="comboModel" open-on-click>
                <ComboboxAnchor class="relative block">
                    <ComboboxInput
                        :id="id"
                        :display-value="displayValue"
                        :placeholder="placeholder"
                        :class="cn(controlClass, 'pr-10')"
                    />
                    <ComboboxTrigger
                        class="absolute inset-y-0 right-0 flex w-10 items-center justify-center"
                        tabindex="-1"
                        :aria-label="label"
                    >
                        <ChevronDown
                            class="size-4 text-muted-foreground"
                            aria-hidden="true"
                        />
                    </ComboboxTrigger>
                </ComboboxAnchor>
                <ComboboxPortal>
                    <ComboboxContent
                        position="popper"
                        :side-offset="4"
                        :class="
                            cn(
                                'max-h-(--reka-combobox-content-available-height) w-(--reka-combobox-trigger-width) min-w-60 overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:animate-in data-[state=open]:fade-in-0',
                                contentClass,
                            )
                        "
                    >
                        <ComboboxViewport
                            data-lenis-prevent
                            class="max-h-72 overflow-y-auto p-1"
                        >
                            <ComboboxEmpty
                                class="px-3 py-4 text-sm text-muted-foreground"
                            >
                                {{ emptyText }}
                            </ComboboxEmpty>
                            <ComboboxItem :value="ANY" :class="itemClass">
                                {{ anyLabel }}
                                <ComboboxItemIndicator class="absolute right-2">
                                    <Check class="size-4" aria-hidden="true" />
                                </ComboboxItemIndicator>
                            </ComboboxItem>
                            <ComboboxGroup
                                v-for="group in groups"
                                :key="group.label"
                            >
                                <ComboboxLabel
                                    class="px-3 pt-3 pb-1 text-overline text-muted-foreground uppercase"
                                >
                                    {{ group.label }}
                                </ComboboxLabel>
                                <ComboboxItem
                                    v-for="option in group.options"
                                    :key="option.value"
                                    :value="option.value"
                                    :text-value="option.label"
                                    :class="itemClass"
                                >
                                    {{ option.label }}
                                    <ComboboxItemIndicator
                                        class="absolute right-2"
                                    >
                                        <Check
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                    </ComboboxItemIndicator>
                                </ComboboxItem>
                            </ComboboxGroup>
                        </ComboboxViewport>
                    </ComboboxContent>
                </ComboboxPortal>
            </ComboboxRoot>
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
                        <optgroup
                            v-for="group in groups"
                            :key="group.label"
                            :label="group.label"
                        >
                            <option
                                v-for="option in group.options"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </optgroup>
                    </select>
                    <ChevronDown :class="chevron" aria-hidden="true" />
                </span>
            </template>
        </ClientOnly>
    </div>
</template>
