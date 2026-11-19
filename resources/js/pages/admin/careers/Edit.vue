<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Copy, ExternalLink, Eye, FileUser, Trash2 } from '@lucide/vue';
import { computed, provide, watch } from 'vue';
import AdminEditor from '@/components/admin/AdminEditor.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import SlugField from '@/components/admin/SlugField.vue';
import ChoiceField from '@/components/admin/catalog/ChoiceField.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import SeoPanel from '@/components/admin/catalog/SeoPanel.vue';
import TextField from '@/components/admin/catalog/TextField.vue';
import {
    assetMapKey,
    createAssetMap,
} from '@/components/admin/catalog/context';
import { errorView } from '@/components/admin/projects/context';
import DatePicker from '@/components/forms/DatePicker.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useConfirm } from '@/composables/useConfirm';
import { index as applicationsIndex } from '@/routes/admin/applications';
import {
    destroy,
    duplicate,
    index,
    preview,
    store,
    update,
} from '@/routes/admin/careers';
import type { JobPostEditProps, JobPostFormData } from '@/types';

// ADM-SCR-10 job post editor (FR-CAR-001): the role, its four text sections, the deadline and status, then
// SEO. A post is a Draft until it is Open; Open needs a description, a deadline of today or later and the
// responsibilities or requirements (VAL-05: only Open is checked, a draft saves with what it has).
const props = defineProps<JobPostEditProps>();

function copyOf(data: JobPostFormData): JobPostFormData {
    return JSON.parse(JSON.stringify(data)) as JobPostFormData;
}

const form = useForm<JobPostFormData>(copyOf(props.form));
const confirm = useConfirm();
const errors = errorView(form);

provide(assetMapKey, createAssetMap(props.assets));

watch(
    () => props.lastSaved,
    () => {
        form.defaults(copyOf(props.form));
        form.reset();
    },
);

function save(): void {
    if (props.job) {
        form.patch(update.url(props.job.id), { preserveScroll: true });
    } else {
        form.post(store.url(), { preserveScroll: true });
    }
}

function fieldId(key: string): string {
    if (key.startsWith('seo.')) {
        return `seo-${key.slice(4).replace('og_image_asset_id', 'og-image').replace(/_/g, '-')}`;
    }

    return `field-${key.replace(/\./g, '-')}`;
}

async function remove(): Promise<void> {
    if (!props.job) {
        return;
    }

    const live = props.job.status !== 'draft';
    const count = props.job.applications_count;
    const answer = await confirm({
        title: 'Delete this job post?',
        subject: props.job.title,
        description:
            (live ? 'It leaves the website at once. ' : '') +
            (count > 0
                ? `Its ${count} ${count === 1 ? 'application stays' : 'applications stay'} under Applications. `
                : '') +
            'The post moves to the Recycle bin and can be restored as a draft for 90 days.',
        confirmLabel: 'Delete job post',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.job.id));
    }
}

const title = computed(
    () => form.title.trim() || props.job?.title || 'New job post',
);
const slugPath = computed(() => `/careers/${form.slug}`);

// The deadline of an open role cannot be in the past; a closed or draft one may be (history).
const deadlineMin = computed(() =>
    form.status === 'open' ? props.options.today : '2020-01-01',
);
const deadlineMax = computed(() => {
    const limit = new Date(`${props.options.today}T00:00:00Z`);

    limit.setUTCFullYear(limit.getUTCFullYear() + 2);
    limit.setUTCDate(limit.getUTCDate() - 1);

    return limit.toISOString().slice(0, 10);
});

const statusHelp = computed(
    () =>
        ({
            draft: 'Hidden from the website. Save the text now and open the role when it is ready.',
            open: 'On the careers page and taking applications until the deadline. It closes by itself at 00:05 the next day.',
            closed: 'Shows “Applications closed” for 30 days, then the page is gone.',
        })[form.status],
);

const saveReason = computed(() => {
    if (!props.can.update) {
        return 'You can read this job post but not change it.';
    }

    return form.title.trim() === '' ? 'Enter the title of the role.' : null;
});
</script>

<template>
    <Head :title="title" />

    <AdminEditor
        :title="title"
        :description="
            job
                ? 'Changes are live as soon as you save: there is no pending version for job posts.'
                : 'Write the role, set the deadline, then save. It stays a draft until you open it.'
        "
        :form="form"
        :last-saved="lastSaved"
        :save-label="job ? 'Save changes' : 'Save job post'"
        :save-reason="saveReason"
        :field-id="fieldId"
        @save="save"
    >
        <template #actions>
            <Link
                :href="index.url()"
                class="text-sm text-muted-foreground underline-offset-4 hover:underline"
                >All job posts</Link
            >
        </template>

        <template #rail>
            <div class="grid gap-5">
                <ChoiceField
                    id="field-status"
                    v-model="form.status"
                    label="Status"
                    required
                    :options="options.statuses"
                    :helper="statusHelp"
                    :error="errors.status"
                />
                <FormField
                    id="field-deadline"
                    label="Deadline"
                    required
                    helper="The last day people can apply (Dhaka time)."
                    :error="errors.deadline"
                >
                    <template #default="{ describedby, invalid }">
                        <DatePicker
                            id="field-deadline"
                            v-model="form.deadline"
                            :min="deadlineMin"
                            :max="deadlineMax"
                            placeholder="Choose a day"
                            :invalid="invalid"
                            :describedby="describedby"
                        />
                    </template>
                </FormField>
                <p
                    v-if="job?.deadline_passed"
                    class="rounded-md bg-warning-50 px-3 py-2 text-sm text-warning-700"
                    role="status"
                >
                    The deadline has passed, so this role takes no more
                    applications. It closes at 00:05.
                </p>
            </div>

            <div v-if="job" class="grid gap-1 border-t pt-4">
                <p
                    class="mb-1 truncate font-mono text-xs text-muted-foreground"
                >
                    {{ job.path }}
                </p>
                <Button
                    v-if="job.status !== 'draft'"
                    as-child
                    variant="ghost"
                    class="w-full justify-start"
                >
                    <a :href="job.path" target="_blank" rel="noopener">
                        <ExternalLink aria-hidden="true" />
                        View on site
                    </a>
                </Button>
                <Button
                    v-else
                    as-child
                    variant="ghost"
                    class="w-full justify-start"
                >
                    <a
                        :href="preview.url(job.id)"
                        target="_blank"
                        rel="noopener"
                    >
                        <Eye aria-hidden="true" />
                        Preview draft
                    </a>
                </Button>
                <Button
                    v-if="can.applications && job.applications_count > 0"
                    as-child
                    variant="ghost"
                    class="w-full justify-start"
                >
                    <Link
                        :href="
                            applicationsIndex.url({
                                query: { 'filter[job]': String(job.id) },
                            })
                        "
                    >
                        <FileUser aria-hidden="true" />
                        {{ job.applications_count }}
                        {{
                            job.applications_count === 1
                                ? 'application'
                                : 'applications'
                        }}
                    </Link>
                </Button>
                <Button
                    v-if="can.duplicate"
                    type="button"
                    variant="ghost"
                    class="w-full justify-start"
                    @click="router.post(duplicate.url(job.id))"
                >
                    <Copy aria-hidden="true" />
                    Duplicate
                </Button>
                <Button
                    v-if="can.delete"
                    type="button"
                    variant="ghost"
                    class="w-full justify-start text-destructive hover:text-destructive"
                    @click="remove"
                >
                    <Trash2 aria-hidden="true" />
                    Delete job post
                </Button>
            </div>
        </template>

        <div class="grid max-w-[1000px] gap-6">
            <AdminSection
                title="The role"
                description="What the role is called and where it sits. This is the top of the job page and one row on the careers page."
            >
                <div class="grid gap-5">
                    <TextField
                        id="field-title"
                        v-model="form.title"
                        label="Title"
                        required
                        :maxlength="options.limits.title"
                        placeholder="Senior sales executive"
                        :error="errors.title"
                    />
                    <SlugField
                        id="field-slug"
                        v-model="form.slug"
                        :from="form.title"
                        source="job_post"
                        :ignore-id="job?.id ?? null"
                        :published="job !== null && job.status !== 'draft'"
                        prefix="/careers/"
                        label="URL"
                        required
                        :error="errors.slug"
                    />
                    <FormField
                        id="field-summary"
                        label="Summary"
                        required
                        :helper="`One sentence under the title and on the careers page. Up to ${options.limits.summary} characters.`"
                        :error="errors.summary"
                    >
                        <template #default="{ describedby, invalid }">
                            <Textarea
                                id="field-summary"
                                v-model="form.summary"
                                rows="2"
                                :maxlength="options.limits.summary"
                                :aria-invalid="invalid || undefined"
                                :aria-describedby="describedby"
                            />
                            <p
                                class="mt-1 text-right text-xs text-muted-foreground tabular-nums"
                            >
                                {{ form.summary.length }} /
                                {{ options.limits.summary }}
                            </p>
                        </template>
                    </FormField>
                    <div class="grid gap-5 md:grid-cols-2">
                        <FormField
                            id="field-department"
                            label="Department"
                            required
                            helper="Pick one from the list or type a new one. It becomes a filter on the careers page."
                            :error="errors.department"
                        >
                            <template #default="{ describedby, invalid }">
                                <Input
                                    id="field-department"
                                    v-model="form.department"
                                    list="job-departments"
                                    :maxlength="options.limits.department"
                                    autocomplete="off"
                                    :aria-invalid="invalid || undefined"
                                    :aria-describedby="describedby"
                                />
                                <datalist id="job-departments">
                                    <option
                                        v-for="department in options.departments"
                                        :key="department"
                                        :value="department"
                                    />
                                </datalist>
                            </template>
                        </FormField>
                        <ChoiceField
                            id="field-employment_type"
                            v-model="form.employment_type"
                            label="Employment type"
                            required
                            :options="options.employment_types"
                            :error="errors.employment_type"
                        />
                    </div>
                    <ChoiceField
                        id="field-office_id"
                        v-model="form.office_id"
                        label="Location"
                        none-label="Project sites"
                        :options="options.offices"
                        :helper="
                            hasOffices
                                ? 'The office the role is based at. Leave it as “Project sites” for roles on the building sites.'
                                : 'No offices yet: add them under Offices. Until then the role is based on the project sites.'
                        "
                        :error="errors.office_id"
                    />
                    <div class="grid gap-5 md:grid-cols-2">
                        <TextField
                            id="field-experience"
                            v-model="form.experience"
                            label="Experience"
                            :maxlength="options.limits.experience"
                            placeholder="3–5 years in property or banking sales"
                            helper="Shown in the role summary. Optional."
                            :error="errors.experience"
                        />
                        <TextField
                            id="field-salary_text"
                            v-model="form.salary_text"
                            label="Salary"
                            :maxlength="options.limits.salary_text"
                            placeholder="৳ 60,000–80,000 a month"
                            helper="Shown only when you fill it in. Leave it empty to say nothing."
                            :error="errors.salary_text"
                        />
                    </div>
                    <div class="grid gap-5 md:grid-cols-2">
                        <TextField
                            id="field-salary_min_bdt"
                            v-model="form.salary_min_bdt"
                            label="Lowest salary (৳ a month)"
                            numeric
                            :min="0"
                            inputmode="numeric"
                            helper="Optional. Used for search engines, never shown on the page."
                            :error="errors.salary_min_bdt"
                        />
                        <TextField
                            id="field-salary_max_bdt"
                            v-model="form.salary_max_bdt"
                            label="Highest salary (৳ a month)"
                            numeric
                            :min="0"
                            inputmode="numeric"
                            :error="errors.salary_max_bdt"
                        />
                    </div>
                </div>
            </AdminSection>

            <AdminSection
                title="The job page"
                description="Four sections under the title. A section left empty is not shown. The title is the page’s only Heading 1, so start at Heading 2."
            >
                <div class="grid gap-5">
                    <FormField
                        id="field-description"
                        label="About the role"
                        helper="Who the person works with and what the days look like. Needed to open the role."
                        :error="errors.description"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="field-description"
                                v-model="form.description"
                                :rows="7"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="field-responsibilities"
                        label="What you will do"
                        helper="A bulleted list works best. Responsibilities or requirements are needed to open the role."
                        :error="errors.responsibilities"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="field-responsibilities"
                                v-model="form.responsibilities"
                                :rows="7"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="field-requirements"
                        label="What you bring"
                        helper="Experience, skills and degrees."
                        :error="errors.requirements"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="field-requirements"
                                v-model="form.requirements"
                                :rows="7"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                    <FormField
                        id="field-benefits"
                        label="What we offer"
                        helper="Pay extras, leave, insurance, training."
                        :error="errors.benefits"
                    >
                        <template #default="{ describedby, invalid }">
                            <RichTextField
                                id="field-benefits"
                                v-model="form.benefits"
                                :rows="6"
                                :invalid="invalid"
                                :describedby="describedby"
                            />
                        </template>
                    </FormField>
                </div>
            </AdminSection>

            <AdminSection
                title="Search and sharing"
                description="How the job looks in Google and when shared."
            >
                <SeoPanel
                    v-model="form.seo"
                    :path="slugPath"
                    :fallback-title="`${form.title || 'Role'} | Gemcon City`"
                    :fallback-description="
                        form.summary || 'The summary of the role.'
                    "
                    :errors="errors"
                />
            </AdminSection>
        </div>
    </AdminEditor>
</template>
