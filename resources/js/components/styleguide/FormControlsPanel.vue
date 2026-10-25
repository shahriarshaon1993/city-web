<script setup lang="ts">
import { CircleAlert } from '@lucide/vue';
import { computed } from 'vue';
import type { StyleguideTheme } from '@/components/styleguide/tokens';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';

// CMP-FORM-01 states on one surface; ids are prefixed per theme so both
// panels can sit on the page together.
const props = defineProps<{ theme: StyleguideTheme }>();

const id = (name: string): string => `${props.theme}-${name}`;

const checkboxes = [
    { key: 'cb1', label: 'Unchecked', value: false, disabled: false },
    { key: 'cb2', label: 'Checked', value: true, disabled: false },
    {
        key: 'cb3',
        label: 'Indeterminate',
        value: 'indeterminate' as const,
        disabled: false,
    },
    { key: 'cb4', label: 'Disabled', value: false, disabled: true },
];

const contactTimes = [
    { key: 'r1', value: 'morning', label: 'Morning', disabled: false },
    { key: 'r2', value: 'evening', label: 'Evening', disabled: false },
    { key: 'r3', value: 'any', label: 'Disabled', disabled: true },
];

const areas = [
    { value: 'bashundhara', label: 'Bashundhara' },
    { value: 'uttara', label: 'Uttara' },
    { value: 'mirpur', label: 'Mirpur' },
];

const title = computed(() =>
    props.theme === 'light' ? 'Light' : '.theme-dark',
);
</script>

<template>
    <div
        :class="[
            'flex flex-col gap-6 rounded-xl border bg-card p-4 text-card-foreground sm:p-6',
            theme === 'dark' && 'theme-dark bg-card',
        ]"
    >
        <h3 class="font-display text-h4">{{ title }}</h3>

        <div class="grid gap-5 md:grid-cols-2">
            <div class="flex flex-col gap-2">
                <Label :for="id('name')" required>Full name</Label>
                <Input
                    :id="id('name')"
                    autocomplete="name"
                    aria-required="true"
                    placeholder="Your name"
                />
            </div>
            <div class="flex flex-col gap-2">
                <Label :for="id('phone')" required>Phone</Label>
                <Input
                    :id="id('phone')"
                    type="tel"
                    autocomplete="tel"
                    aria-required="true"
                    aria-invalid="true"
                    :aria-describedby="id('phone-error')"
                    default-value="0171"
                />
                <p
                    :id="id('phone-error')"
                    class="flex items-center gap-1.5 text-xs text-destructive"
                >
                    <CircleAlert class="size-3.5 shrink-0" />
                    Enter an 11-digit mobile number.
                </p>
            </div>
            <div class="flex flex-col gap-2">
                <Label :for="id('email')">Email</Label>
                <Input
                    :id="id('email')"
                    type="email"
                    autocomplete="email"
                    :aria-describedby="id('email-help')"
                />
                <p :id="id('email-help')" class="text-xs text-muted-foreground">
                    We reply within one working day.
                </p>
            </div>
            <div class="flex flex-col gap-2">
                <Label :for="id('area')">Area</Label>
                <Select>
                    <SelectTrigger :id="id('area')">
                        <SelectValue placeholder="Choose an area" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="area in areas"
                            :key="area.value"
                            :value="area.value"
                        >
                            {{ area.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex flex-col gap-2">
                <Label :for="id('disabled')">Disabled</Label>
                <Input
                    :id="id('disabled')"
                    disabled
                    default-value="Not editable"
                />
            </div>
            <div class="flex flex-col gap-2">
                <Label :for="id('readonly')">Read-only</Label>
                <Input
                    :id="id('readonly')"
                    readonly
                    default-value="GC-2026-0012"
                />
            </div>
            <div class="flex flex-col gap-2 md:col-span-2">
                <Label :for="id('message')">Message</Label>
                <Textarea
                    :id="id('message')"
                    placeholder="Tell us what you are looking for"
                />
            </div>
        </div>

        <fieldset class="flex flex-col gap-3">
            <legend class="mb-3 text-sm font-medium">Checkboxes</legend>
            <div
                v-for="box in checkboxes"
                :key="box.key"
                class="flex items-center gap-3"
            >
                <Checkbox
                    :id="id(box.key)"
                    :default-value="box.value"
                    :disabled="box.disabled"
                />
                <Label :for="id(box.key)">{{ box.label }}</Label>
            </div>
        </fieldset>

        <fieldset class="flex flex-col gap-3">
            <legend class="mb-3 text-sm font-medium">
                Preferred contact time
            </legend>
            <RadioGroup default-value="morning">
                <div
                    v-for="time in contactTimes"
                    :key="time.key"
                    class="flex items-center gap-3"
                >
                    <RadioGroupItem
                        :id="id(time.key)"
                        :value="time.value"
                        :disabled="time.disabled"
                    />
                    <Label :for="id(time.key)">{{ time.label }}</Label>
                </div>
            </RadioGroup>
        </fieldset>

        <div class="flex flex-wrap items-center gap-6">
            <div class="flex items-center gap-3">
                <Switch :id="id('sw1')" :default-value="true" />
                <Label :for="id('sw1')">Newsletter</Label>
            </div>
            <div class="flex items-center gap-3">
                <Switch :id="id('sw2')" disabled />
                <Label :for="id('sw2')">Disabled</Label>
            </div>
        </div>

        <ToggleGroup
            type="single"
            variant="outline"
            default-value="3"
            aria-label="Bedrooms"
        >
            <ToggleGroupItem v-for="n in ['2', '3', '4']" :key="n" :value="n">
                {{ n }} bed
            </ToggleGroupItem>
        </ToggleGroup>

        <div class="flex flex-wrap gap-3">
            <Button>Primary</Button>
            <Button variant="accent">Enquire</Button>
            <Button variant="outline">Outline</Button>
            <Popover>
                <PopoverTrigger as-child>
                    <Button variant="ghost">Popover</Button>
                </PopoverTrigger>
                <PopoverContent class="text-sm">
                    Popovers use the --popover tokens.
                </PopoverContent>
            </Popover>
        </div>
    </div>
</template>
