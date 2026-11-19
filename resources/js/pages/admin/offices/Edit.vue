<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, X } from '@lucide/vue';
import { computed, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useConfirm } from '@/composables/useConfirm';
import { destroy, index, store, update } from '@/routes/admin/offices';
import type { OfficeEditProps } from '@/types';

// ADM-SCR-09 office editor (FR-SOC-005): address, phones, email, opening hours as day ranges with
// times, and the map point. One office is the head office; marking this one moves the mark here.
const props = defineProps<OfficeEditProps>();

function copyOf(data: OfficeEditProps['form']): OfficeEditProps['form'] {
    return JSON.parse(JSON.stringify(data)) as OfficeEditProps['form'];
}

const form = useForm(copyOf(props.form));
const confirm = useConfirm();

watch(
    () => props.lastSaved,
    () => {
        form.defaults(copyOf(props.form));
        form.reset();
    },
);

function save(): void {
    if (props.office) {
        form.patch(update.url(props.office.id), { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}

async function remove(): Promise<void> {
    if (!props.office) {
        return;
    }

    const answer = await confirm({
        title: 'Delete this office?',
        subject: props.office.name,
        description:
            'It leaves the contact page and the footer at once. It moves to the Recycle bin and can be restored for 90 days.',
        confirmLabel: 'Delete office',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.office.id));
    }
}

// --- phones and hours ----------------------------------------------------------------------------
function addPhone(): void {
    if (form.phones.length < 5) {
        form.phones = [...form.phones, ''];
    }
}

function removePhone(position: number): void {
    form.phones = form.phones.filter((_, index) => index !== position);

    if (form.phones.length === 0) {
        form.phones = [''];
    }
}

function addHours(): void {
    if (form.hours.length < 7) {
        form.hours = [
            ...form.hours,
            { days: [], opens: '09:30', closes: '18:30' },
        ];
    }
}

function removeHours(position: number): void {
    form.hours = form.hours.filter((_, index) => index !== position);
}

function toggleDay(position: number, day: string): void {
    const row = form.hours[position];

    row.days = row.days.includes(day)
        ? row.days.filter((item) => item !== day)
        : props.options.days.filter(
              (item) => item === day || row.days.includes(item),
          );
}

function hoursError(position: number): string | undefined {
    const keys = ['days', 'opens', 'closes'].map(
        (key) => `hours.${position}.${key}`,
    );

    return keys
        .map((key) => (form.errors as Record<string, string | undefined>)[key])
        .find(Boolean);
}

function phoneError(position: number): string | undefined {
    return (form.errors as Record<string, string | undefined>)[
        `phones.${position}`
    ];
}

const canSave = computed(
    () =>
        form.name.trim() !== '' &&
        form.address.trim() !== '' &&
        form.city.trim() !== '' &&
        form.phones.some((phone) => phone.trim() !== ''),
);
const fieldId = (key: string): string => {
    const [first, second] = key.split('.');

    return first === 'phones' ? `office-phone-${second}` : `office-${first}`;
};
</script>

<template>
    <Head :title="office?.name ?? 'New office'" />

    <AdminEditor
        :title="form.name.trim() || office?.name || 'New office'"
        description="Shown on the contact page, in the footer and to search engines as business details."
        :form="form"
        :last-saved="lastSaved"
        :save-label="office ? 'Save changes' : 'Add office'"
        :save-reason="
            canSave
                ? null
                : 'Enter a name, an address, a city and a phone number.'
        "
        :field-id="fieldId"
        @save="save"
    >
        <template #actions>
            <Link
                :href="index.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All offices</Link
            >
            <Button
                v-if="office"
                type="button"
                variant="outline"
                @click="remove"
            >
                <Trash2 aria-hidden="true" />
                Delete
            </Button>
        </template>

        <template #rail>
            <SwitchRow
                id="office-is_head_office"
                v-model="form.is_head_office"
                label="Head office"
                helper="Listed first. Only one office can be the head office."
            />
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="Address"
                description="Where visitors find this office."
            >
                <div class="grid gap-5">
                    <TextField
                        id="office-name"
                        v-model="form.name"
                        label="Name"
                        required
                        :maxlength="options.limits.name"
                        placeholder="Head office"
                        :error="form.errors.name"
                    />
                    <TextField
                        id="office-address"
                        v-model="form.address"
                        label="Street address"
                        required
                        :maxlength="255"
                        :error="form.errors.address"
                    />
                    <div class="grid gap-5 sm:grid-cols-[2fr_1fr]">
                        <TextField
                            id="office-city"
                            v-model="form.city"
                            label="City"
                            required
                            :maxlength="60"
                            :error="form.errors.city"
                        />
                        <TextField
                            id="office-postal_code"
                            v-model="form.postal_code"
                            label="Postcode"
                            :maxlength="10"
                            inputmode="numeric"
                            :error="form.errors.postal_code"
                        />
                    </div>
                </div>
            </AdminSection>

            <AdminSection
                title="Phone and email"
                description="At least one phone number. Numbers are saved in the international format."
            >
                <div class="grid gap-5">
                    <div class="grid gap-3">
                        <FormField
                            v-for="(_, position) in form.phones"
                            :id="`office-phone-${position}`"
                            :key="position"
                            :label="`Phone ${position + 1}`"
                            :required="position === 0"
                            :helper="
                                position === 0
                                    ? 'For example 09600-000000 or +880 1700-000000.'
                                    : undefined
                            "
                            :error="
                                phoneError(position) ??
                                (position === 0
                                    ? form.errors.phones
                                    : undefined)
                            "
                        >
                            <template #default="{ id, describedby, invalid }">
                                <div class="flex items-center gap-2">
                                    <Input
                                        :id="id"
                                        v-model="form.phones[position]"
                                        type="tel"
                                        inputmode="tel"
                                        maxlength="30"
                                        autocomplete="off"
                                        :aria-describedby="describedby"
                                        :aria-invalid="invalid || undefined"
                                    />
                                    <Button
                                        v-if="form.phones.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        :aria-label="`Remove phone ${position + 1}`"
                                        @click="removePhone(position)"
                                    >
                                        <X aria-hidden="true" />
                                    </Button>
                                </div>
                            </template>
                        </FormField>
                        <div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                :disabled="form.phones.length >= 5"
                                @click="addPhone"
                            >
                                <Plus aria-hidden="true" />
                                Add phone
                            </Button>
                        </div>
                    </div>
                    <TextField
                        id="office-email"
                        v-model="form.email"
                        label="Email"
                        type="email"
                        :maxlength="190"
                        autocomplete="off"
                        :error="form.errors.email"
                    />
                </div>
            </AdminSection>

            <AdminSection
                title="Opening hours"
                description="One row per set of days that share the same hours. Days left out are shown as closed."
            >
                <div class="grid gap-4">
                    <div
                        v-for="(row, position) in form.hours"
                        :key="position"
                        class="grid gap-3 rounded-lg border p-4"
                    >
                        <div
                            class="flex flex-wrap gap-2"
                            role="group"
                            :aria-label="`Days for hours row ${position + 1}`"
                        >
                            <Button
                                v-for="day in options.days"
                                :key="day"
                                type="button"
                                size="sm"
                                :variant="
                                    row.days.includes(day)
                                        ? 'primary'
                                        : 'outline'
                                "
                                :aria-pressed="row.days.includes(day)"
                                @click="toggleDay(position, day)"
                                >{{ day }}</Button
                            >
                        </div>
                        <div class="flex flex-wrap items-end gap-4">
                            <TextField
                                :id="`office-hours-${position}-opens`"
                                v-model="row.opens"
                                label="Opens"
                                type="time"
                            />
                            <TextField
                                :id="`office-hours-${position}-closes`"
                                v-model="row.closes"
                                label="Closes"
                                type="time"
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                @click="removeHours(position)"
                            >
                                <Trash2 aria-hidden="true" />
                                Remove row
                            </Button>
                        </div>
                        <p
                            v-if="hoursError(position)"
                            class="text-sm text-destructive"
                            role="alert"
                        >
                            {{ hoursError(position) }}
                        </p>
                    </div>
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="form.hours.length >= 7"
                            @click="addHours"
                        >
                            <Plus aria-hidden="true" />
                            Add hours
                        </Button>
                    </div>
                </div>
            </AdminSection>

            <AdminSection
                title="Map"
                description="The point on the map. Optional: without it the office has no map. In Google Maps, right-click the place and copy the two numbers."
            >
                <div class="grid gap-5 sm:grid-cols-2">
                    <TextField
                        id="office-latitude"
                        v-model="form.latitude"
                        label="Latitude"
                        inputmode="decimal"
                        placeholder="23.7925"
                        helper="Between 20.5 and 26.7."
                        :error="form.errors.latitude"
                    />
                    <TextField
                        id="office-longitude"
                        v-model="form.longitude"
                        label="Longitude"
                        inputmode="decimal"
                        placeholder="90.4078"
                        helper="Between 88.0 and 92.7."
                        :error="form.errors.longitude"
                    />
                </div>
            </AdminSection>
        </div>
    </AdminEditor>
</template>
