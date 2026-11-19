<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Download, Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminRadioCards from '@/components/admin/AdminRadioCards.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { formatDateTime } from '@/lib/admin-format';
import { store } from '@/routes/admin/import';
import type { ExportRun, ImportRun, ImportTypeOption } from '@/types';

// ADM-IO-001 step 1 (choose what and upload the file) plus the history of the person's imports and of
// the large exports that were prepared in the background (ADM-TBL-012).
const props = defineProps<{
    types: ImportTypeOption[];
    imports: ImportRun[];
    exports: ExportRun[];
    limits: { max_mb: number; max_rows: number; preview_rows: number };
}>();

const form = useForm<{ type: string; file: File | null }>({
    type: props.types[0]?.value ?? '',
    file: null,
});
const clientError = ref<string | null>(null);
const input = ref<HTMLInputElement | null>(null);

const selected = computed(() =>
    props.types.find((type) => type.value === form.type),
);

const options = computed(() =>
    props.types.map((type) => ({
        value: type.value,
        label: type.label,
        description: type.help,
    })),
);

function choose(event: Event): void {
    const chosen = (event.target as HTMLInputElement).files?.[0] ?? null;

    clientError.value = null;
    form.clearErrors();
    form.file = null;

    if (!chosen) {
        return;
    }

    if (!/\.(csv|txt|xlsx)$/i.test(chosen.name)) {
        clientError.value =
            'The file must be a CSV (.csv) or Excel (.xlsx) file.';

        return;
    }

    if (chosen.size > props.limits.max_mb * 1024 * 1024) {
        clientError.value = `The file must be ${props.limits.max_mb} MB or smaller.`;

        return;
    }

    form.file = chosen;
}

function submit(): void {
    if (!form.file) {
        clientError.value = 'Choose a CSV or Excel file first.';

        return;
    }

    form.post(store.url(), { forceFormData: true });
}

const importTone: Record<string, string> = {
    uploaded: 'warning',
    queued: 'info',
    running: 'info',
    finished: 'success',
    failed: 'error',
};

const exportTone: Record<string, string> = {
    queued: 'info',
    running: 'info',
    ready: 'success',
    expired: 'neutral',
    failed: 'error',
};

const exportLabel: Record<string, string> = {
    queued: 'Waiting to start',
    running: 'Being prepared',
    ready: 'Ready',
    expired: 'Link expired',
    failed: 'Failed',
};
</script>

<template>
    <Head title="Import & export" />

    <AdminPageHeader
        title="Import & export"
        description="Bring leads, subscribers, redirects, locations and FAQs in from a spreadsheet, and find the large exports you asked for."
    />

    <div class="flex max-w-[1100px] flex-col gap-6">
        <AdminSection
            title="Import from a file"
            :description="`CSV or Excel (.xlsx), up to ${limits.max_mb} MB and ${limits.max_rows.toLocaleString('en-GB')} rows. You map the columns and check the first ${limits.preview_rows} rows before anything is saved.`"
        >
            <form class="grid gap-6" novalidate @submit.prevent="submit">
                <div>
                    <AdminRadioCards
                        v-model="form.type"
                        legend="What are you importing?"
                        name="import-type"
                        :options="options"
                    />
                    <InputError :message="form.errors.type" />
                </div>

                <div
                    v-if="selected"
                    class="grid gap-3 rounded-lg border bg-muted/40 p-4 text-sm"
                >
                    <p class="font-medium">
                        Columns {{ selected.label }} understands
                    </p>
                    <ul class="grid gap-1 text-muted-foreground sm:grid-cols-2">
                        <li v-for="field in selected.fields" :key="field.key">
                            <span class="text-foreground">{{
                                field.label
                            }}</span>
                            <span v-if="field.required"> (required)</span>
                            <span v-if="field.hint"> — {{ field.hint }}</span>
                        </li>
                    </ul>
                    <p>
                        <a
                            :href="selected.template_url"
                            class="inline-flex items-center gap-1.5 text-primary underline-offset-4 hover:underline"
                        >
                            <Download class="size-4" aria-hidden="true" />
                            Download a template file
                        </a>
                    </p>
                </div>

                <div class="grid gap-2">
                    <Label for="import-file">File</Label>
                    <input
                        id="import-file"
                        ref="input"
                        type="file"
                        accept=".csv,.txt,.xlsx"
                        class="block w-full max-w-md text-sm file:mr-3 file:rounded-md file:border file:bg-background file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-muted"
                        aria-describedby="import-file-help"
                        :aria-invalid="
                            !!(clientError || form.errors.file) || undefined
                        "
                        @change="choose"
                    />
                    <p
                        id="import-file-help"
                        class="text-xs text-muted-foreground"
                    >
                        The first row must hold the column names. A file from
                        Facebook Lead Ads works as it is.
                    </p>
                    <InputError :message="clientError ?? form.errors.file" />
                </div>

                <div>
                    <Button
                        type="submit"
                        :loading="form.processing"
                        :disabled="types.length === 0"
                    >
                        <template #icon><Upload aria-hidden="true" /></template>
                        Upload and continue
                    </Button>
                </div>
            </form>
        </AdminSection>

        <AdminSection
            title="Your recent imports"
            description="The report of each import, with the rows that had a problem. Files are deleted when an import ends; problem files stay for 7 days."
        >
            <p
                v-if="imports.length === 0"
                class="text-sm text-muted-foreground"
            >
                You have not imported anything yet. Choose a type above to
                start.
            </p>
            <ul v-else class="divide-y">
                <li
                    v-for="run in imports"
                    :key="run.id"
                    class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-3 first:pt-0 last:pb-0"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ run.type_label }} ·
                            <span class="font-normal">{{ run.file }}</span>
                        </p>
                        <p class="text-xs text-muted-foreground">
                            <template v-if="run.status === 'finished'">
                                {{ run.created }} new,
                                {{ run.updated }} updated,
                                {{ run.skipped }} skipped, {{ run.failed }} with
                                a problem ·
                            </template>
                            <template v-else-if="run.working">
                                {{ run.processed_rows }} of
                                {{ run.total_rows }} rows ·
                            </template>
                            <template v-else-if="run.message">
                                {{ run.message }} ·
                            </template>
                            <template v-if="run.created_at">{{
                                formatDateTime(run.created_at)
                            }}</template>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge
                            :label="run.status_label"
                            :tone="importTone[run.status] ?? 'neutral'"
                        />
                        <Button as-child variant="outline" size="sm">
                            <Link :href="run.url">
                                {{
                                    run.status === 'uploaded'
                                        ? 'Continue'
                                        : 'Open report'
                                }}
                            </Link>
                        </Button>
                    </div>
                </li>
            </ul>
        </AdminSection>

        <AdminSection
            title="Large exports"
            description="An export of more than 5,000 rows is prepared in the background. You get a notification, and the file waits here behind a link that works for 24 hours."
        >
            <p
                v-if="exports.length === 0"
                class="text-sm text-muted-foreground"
            >
                No large exports yet. Use Export on any table; the file arrives
                here when the list is longer than 5,000 rows.
            </p>
            <ul v-else class="divide-y">
                <li
                    v-for="run in exports"
                    :key="run.id"
                    class="flex flex-wrap items-center justify-between gap-x-6 gap-y-2 py-3 first:pt-0 last:pb-0"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ run.filename }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            <template v-if="run.rows !== null"
                                >{{ run.rows.toLocaleString('en-GB') }} rows ·
                            </template>
                            <template
                                v-if="run.status === 'failed' && run.message"
                                >{{ run.message }} ·
                            </template>
                            <template
                                v-if="run.status === 'ready' && run.expires_at"
                                >Link works until
                                {{ formatDateTime(run.expires_at) }}</template
                            >
                            <template v-else-if="run.created_at">{{
                                formatDateTime(run.created_at)
                            }}</template>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <StatusBadge
                            :label="exportLabel[run.status] ?? run.status"
                            :tone="exportTone[run.status] ?? 'neutral'"
                        />
                        <Button
                            v-if="run.download_url"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <a :href="run.download_url">
                                <Download aria-hidden="true" />
                                Download
                            </a>
                        </Button>
                    </div>
                </li>
            </ul>
        </AdminSection>
    </div>
</template>
