<script setup lang="ts">
import AdminSection from '@/components/admin/AdminSection.vue';
import SlugField from '@/components/admin/SlugField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RelatedPicker from '@/components/admin/catalog/RelatedPicker.vue';
import SwitchRow from '@/components/admin/catalog/SwitchRow.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import FormField from '@/components/forms/FormField.vue';
import { useProjectEditor } from './context';

// Basics: identity (name, URL, tagline), classification (segment, status, booking badge, area,
// featured) and the optional hand-picked similar projects (FR-PROJ-007).
const { form, options, errors, published, projectId } = useProjectEditor();
</script>

<template>
    <div class="grid gap-6">
        <AdminSection
            title="Identity"
            description="How the project is named and where it lives on the website."
        >
            <div class="grid gap-5">
                <TextField
                    id="field-name"
                    v-model="form.name"
                    label="Project name"
                    required
                    :maxlength="90"
                    helper="Shown on cards and as the page heading. 3 to 90 characters, unique among projects."
                    :error="errors.name"
                />
                <SlugField
                    id="field-slug"
                    v-model="form.slug"
                    :from="form.name"
                    source="project"
                    :ignore-id="projectId"
                    :published="published"
                    prefix="/projects/"
                    label="URL"
                    required
                    :error="errors.slug"
                />
                <TextField
                    id="field-tagline"
                    v-model="form.tagline"
                    label="Tagline"
                    :maxlength="120"
                    helper="Shown under the project name on cards. Up to 120 characters."
                    :error="errors.tagline"
                />
            </div>
        </AdminSection>

        <AdminSection
            title="Classification"
            description="Visitors filter projects by these, and the badges on photos come from them."
        >
            <div class="grid gap-5 md:grid-cols-2">
                <ChoiceField
                    id="field-segment"
                    v-model="form.segment"
                    label="Segment"
                    required
                    :options="options.segments"
                    helper="Land projects need no unit types."
                    :error="errors.segment"
                />
                <ChoiceField
                    id="field-lifecycle"
                    v-model="form.lifecycle"
                    label="Project status"
                    required
                    :options="options.lifecycles"
                    helper="Upcoming, ongoing, ready or handed over."
                    :error="errors.lifecycle"
                />
                <ChoiceField
                    id="field-booking_state"
                    v-model="form.booking_state"
                    label="Booking badge"
                    :options="options.bookings"
                    helper="The badge on the project photo. “None” shows no badge."
                    :error="errors.booking_state"
                />
                <ChoiceField
                    id="field-location_id"
                    v-model="form.location_id"
                    label="Area"
                    required
                    placeholder="Choose an area"
                    :options="options.areas"
                    helper="The area page lists this project."
                    :error="errors.location_id"
                />
            </div>
            <div class="mt-5">
                <SwitchRow
                    id="field-is_featured"
                    v-model="form.is_featured"
                    label="Featured on the home page"
                    helper="Featured projects appear on the home page in the order set under Featured order."
                    :error="errors.is_featured"
                />
            </div>
        </AdminSection>

        <AdminSection
            title="Similar projects"
            description="Shown at the end of the project page."
        >
            <FormField
                id="field-similar_ids"
                label="Hand-picked projects"
                helper="Up to 3. When empty, the site picks up to 3 published projects of the same segment and city."
                :error="errors.similar_ids"
            >
                <template #default="{ describedby, invalid }">
                    <RelatedPicker
                        id="field-similar_ids"
                        v-model="form.similar_ids"
                        :options="options.similar"
                        :max="options.limits.similar"
                        noun="a project"
                        :describedby="describedby"
                        :invalid="invalid"
                    />
                </template>
            </FormField>
        </AdminSection>
    </div>
</template>
