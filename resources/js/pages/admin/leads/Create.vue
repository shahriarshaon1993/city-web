<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import DateTimePicker from '@/components/forms/DateTimePicker.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import { dhakaInputValue } from '@/lib/admin-format';
import { index, store } from '@/routes/admin/leads';
import type { NewLeadOptions } from '@/types';

// DEC-33 (owner: a full page, not a dialog): "New lead" for an enquiry that did not come through the
// website — a phone call, a walk-in, an email, an event. The same rules apply as for a form: a phone
// that asked about the same project today becomes a touch on that lead, and the lead gets an owner
// unless you choose one. Limits mirror StoreManualLeadRequest (VAL-03).
const props = defineProps<{ options: NewLeadOptions }>();

const MESSAGE_MAX = 2000;

const form = useForm({
    name: '',
    phone_country: props.options.defaultCountry,
    phone: '',
    whatsapp: false,
    email: '',
    channel: null as string | null,
    project_id: null as number | null,
    unit_type_id: null as number | null,
    assigned_to: null as number | null,
    follow_up_at: '',
    message: '',
});

const countries = computed(() =>
    props.options.countries.map((country) => ({
        value: country.code,
        label: `${country.name} (+${country.dial})`,
    })),
);
const projects = computed(() =>
    props.options.projects.map((project) => ({
        value: project.id,
        label: project.name,
    })),
);
const unitTypes = computed(
    () =>
        props.options.projects
            .find((project) => project.id === form.project_id)
            ?.unit_types.map((unit) => ({
                value: unit.id,
                label: unit.name,
            })) ?? [],
);
const owners = computed(() =>
    props.options.assignees.map((person) => ({
        value: person.id,
        label: person.name,
    })),
);

const earliest = `${dhakaInputValue(new Date()).slice(0, 10)}T00:00`;
const latest = dhakaInputValue(new Date(Date.now() + 2 * 365 * 86_400_000));

watch(
    () => form.project_id,
    () => {
        form.unit_type_id = null;
    },
);

const fieldId = (key: string): string => `lead-${key}`;

function save(): void {
    form.post(store.url(), { preserveScroll: true });
}

const canSave = computed(
    () =>
        form.name.trim() !== '' &&
        form.phone.trim() !== '' &&
        form.channel !== null,
);
</script>

<template>
    <Head title="New lead" />

    <AdminEditor
        title="New lead"
        description="For an enquiry that did not come through the website: a phone call, a walk-in, an email or an event."
        :form="form"
        save-label="Add lead"
        :save-reason="
            canSave
                ? null
                : 'Enter a name, a phone number and how they got in touch.'
        "
        :field-id="fieldId"
        @save="save"
    >
        <template #actions>
            <Link
                :href="index.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All leads</Link
            >
        </template>

        <template #rail>
            <div class="grid gap-3 text-xs text-muted-foreground">
                <p class="font-medium text-foreground">What happens next</p>
                <p>
                    If this number asked about the same project in the last 24
                    hours, the enquiry is added to that lead instead of making a
                    second one.
                </p>
                <p>
                    A new lead goes to the person you choose. Without a choice
                    it follows the usual rule: the project's default assignee,
                    then the Sales Executive with the fewest open leads.
                </p>
            </div>
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="Who is asking"
                description="How we reach them. The phone is saved in the international format."
            >
                <div class="grid gap-5">
                    <TextField
                        id="lead-name"
                        v-model="form.name"
                        label="Name"
                        required
                        :maxlength="120"
                        autocomplete="off"
                        :error="form.errors.name"
                    />

                    <div
                        class="grid gap-5 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]"
                    >
                        <FormField
                            id="lead-phone_country"
                            label="Country"
                            :error="form.errors.phone_country"
                        >
                            <template #default="{ id, describedby, invalid }">
                                <AdminSelect
                                    :id="id"
                                    v-model="form.phone_country"
                                    :options="countries"
                                    :invalid="invalid"
                                    :describedby="describedby"
                                />
                            </template>
                        </FormField>
                        <TextField
                            id="lead-phone"
                            v-model="form.phone"
                            label="Phone"
                            required
                            type="tel"
                            inputmode="tel"
                            :maxlength="30"
                            autocomplete="off"
                            helper="A Bangladesh mobile like 01712 345678, or the full number with its country code."
                            :error="form.errors.phone"
                        />
                    </div>

                    <label class="flex items-center gap-3 text-sm">
                        <Checkbox
                            :model-value="form.whatsapp"
                            @update:model-value="
                                (value) => (form.whatsapp = value === true)
                            "
                        />
                        This number is on WhatsApp
                    </label>

                    <TextField
                        id="lead-email"
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
                title="What they asked about"
                description="The channel, the project and what they said."
            >
                <div class="grid gap-5">
                    <FormField
                        id="lead-channel"
                        label="How did they get in touch?"
                        required
                        :error="form.errors.channel"
                    >
                        <template #default="{ id, describedby, invalid }">
                            <AdminSelect
                                :id="id"
                                v-model="form.channel"
                                :options="options.channels"
                                placeholder="Choose a channel"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <FormField
                            id="lead-project_id"
                            label="Project"
                            :error="form.errors.project_id"
                        >
                            <template #default="{ id, describedby, invalid }">
                                <AdminSelect
                                    :id="id"
                                    v-model="form.project_id"
                                    :options="projects"
                                    none-label="No project"
                                    placeholder="No project"
                                    :invalid="invalid"
                                    :describedby="describedby"
                                />
                            </template>
                        </FormField>
                        <FormField
                            id="lead-unit_type_id"
                            label="Unit type"
                            :error="form.errors.unit_type_id"
                        >
                            <template #default="{ id, describedby, invalid }">
                                <AdminSelect
                                    :id="id"
                                    v-model="form.unit_type_id"
                                    :options="unitTypes"
                                    none-label="Any"
                                    placeholder="Any"
                                    :disabled="unitTypes.length === 0"
                                    :invalid="invalid"
                                    :describedby="describedby"
                                />
                            </template>
                        </FormField>
                    </div>

                    <FormField
                        id="lead-message"
                        label="What did they ask?"
                        :counter="{
                            length: form.message.length,
                            max: MESSAGE_MAX,
                        }"
                        :error="form.errors.message"
                    >
                        <template #default="{ id, describedby, invalid }">
                            <Textarea
                                :id="id"
                                v-model="form.message"
                                :maxlength="MESSAGE_MAX"
                                rows="4"
                                :aria-describedby="describedby"
                                :aria-invalid="invalid || undefined"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>

            <AdminSection
                title="Handling"
                description="Who looks after this lead and when to call back."
            >
                <div class="grid gap-5">
                    <FormField
                        v-if="options.assignees.length > 0"
                        id="lead-assigned_to"
                        label="Owner"
                        helper="Leave empty to follow the usual rule."
                        :error="form.errors.assigned_to"
                    >
                        <template #default="{ id, describedby, invalid }">
                            <AdminSelect
                                :id="id"
                                v-model="form.assigned_to"
                                :options="owners"
                                none-label="Choose automatically"
                                placeholder="Choose automatically"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>

                    <FormField
                        id="lead-follow_up_at"
                        label="Follow up on"
                        helper="Optional. Dhaka time (UTC+6)."
                        :error="form.errors.follow_up_at"
                    >
                        <template #default="{ id, describedby, invalid }">
                            <div class="flex flex-wrap items-center gap-2">
                                <DateTimePicker
                                    :id="id"
                                    v-model="form.follow_up_at"
                                    :min="earliest"
                                    :max="latest"
                                    placeholder="No follow-up"
                                    :invalid="invalid"
                                    :describedby="describedby"
                                />
                                <Button
                                    v-if="form.follow_up_at"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="form.follow_up_at = ''"
                                    >Clear</Button
                                >
                            </div>
                        </template>
                    </FormField>
                </div>
            </AdminSection>
        </div>
    </AdminEditor>
</template>
