<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Play } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminSection from '@/components/admin/AdminSection.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime } from '@/lib/admin-format';
import { index } from '@/routes/admin/import';
import {
    destroy,
    mapping as saveMapping,
    start,
} from '@/routes/admin/import/runs';
import type {
    ImportField,
    ImportOption,
    ImportPreview,
    ImportRun,
} from '@/types';

// ADM-IO-001 steps 2–5 on one page that follows the run's status: map the columns and check the first
// rows (uploaded), watch the queued import (queued / running), read the report (finished / failed).
const props = defineProps<{
    run: ImportRun;
    canEdit: boolean;
    fields: ImportField[];
    options: ImportOption[];
    columns: string[];
    mapping: Record<string, number | null>;
    preview: ImportPreview | null;
    limits: { preview_rows: number };
    errors?: Record<string, string>;
}>();

const NONE = 'none';
const confirm = useConfirm();

const chosen = ref<Record<string, string>>({});
const confirmed = ref<Record<string, boolean>>({});
const starting = ref(false);
const startErrors = ref<Record<string, string>>({});

watch(
    () => props.mapping,
    (mapping) => {
        chosen.value = Object.fromEntries(
            props.fields.map((field) => [
                field.key,
                mapping[field.key] === null || mapping[field.key] === undefined
                    ? NONE
                    : String(mapping[field.key]),
            ]),
        );
    },
    { immediate: true },
);

const missing = computed(() =>
    props.fields.filter(
        (field) => field.required && chosen.value[field.key] === NONE,
    ),
);

const mappedFields = computed(() =>
    props.fields.filter((field) => chosen.value[field.key] !== NONE),
);

function pick(key: string, value: unknown): void {
    chosen.value = { ...chosen.value, [key]: String(value) };

    router.put(
        saveMapping.url(props.run.id),
        {
            mapping: Object.fromEntries(
                Object.entries(chosen.value).map(([field, column]) => [
                    field,
                    column === NONE ? null : Number(column),
                ]),
            ),
        },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['mapping', 'preview', 'run'],
        },
    );
}

function columnLabel(index: number): string {
    const header = props.columns[index];

    return header ? `${index + 1}. ${header}` : `Column ${index + 1}`;
}

function startImport(): void {
    startErrors.value = {};

    router.post(
        start.url(props.run.id),
        { confirm: confirmed.value },
        {
            preserveScroll: true,
            onStart: () => {
                starting.value = true;
            },
            onError: (errors) => {
                startErrors.value = errors;
            },
            onFinish: () => {
                starting.value = false;
            },
        },
    );
}

async function cancel(): Promise<void> {
    const answer = await confirm({
        title: 'Cancel this import?',
        subject: props.run.file,
        description:
            'The uploaded file is deleted and nothing is imported. You can upload it again later.',
        confirmLabel: 'Cancel import',
        destructive: true,
    });

    if (answer) {
        router.delete(destroy.url(props.run.id));
    }
}

// While the import runs the page asks for fresh numbers every two seconds.
let timer: ReturnType<typeof setInterval> | null = null;

function stopPolling(): void {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}

function startPolling(): void {
    stopPolling();
    timer = setInterval(() => {
        if (props.run.working) {
            router.reload({ only: ['run'] });
        } else {
            stopPolling();
        }
    }, 2000);
}

onMounted(() => {
    if (props.run.working) {
        startPolling();
    }
});

onBeforeUnmount(stopPolling);

watch(
    () => props.run.working,
    (working) => {
        if (working) {
            startPolling();
        } else {
            stopPolling();
        }
    },
);

const percent = computed(() =>
    props.run.total_rows === 0
        ? 0
        : Math.min(
              100,
              Math.round(
                  (props.run.processed_rows / props.run.total_rows) * 100,
              ),
          ),
);

const tone = computed(
    () =>
        ({
            uploaded: 'warning',
            queued: 'info',
            running: 'info',
            finished: 'success',
            failed: 'error',
        })[props.run.status] ?? 'neutral',
);

const outcomeTone: Record<string, string> = {
    create: 'success',
    update: 'info',
    skip: 'neutral',
    fail: 'error',
};

const requiredOptionsTicked = computed(() =>
    props.options.every(
        (option) => !option.required || confirmed.value[option.key],
    ),
);

const startReason = computed(() => {
    if (!props.canEdit) {
        return 'Only the person who uploaded this file can start it.';
    }

    if (missing.value.length > 0) {
        return `Choose a column for ${missing.value.map((field) => field.label).join(' and ')} first.`;
    }

    if (!requiredOptionsTicked.value) {
        return 'Tick the confirmation first.';
    }

    return null;
});
</script>

<template>
    <Head :title="`${run.type_label} import`" />

    <AdminPageHeader
        :title="`${run.type_label} import`"
        :description="run.file"
    >
        <template #actions>
            <StatusBadge :label="run.status_label" :tone="tone" />
        </template>
    </AdminPageHeader>

    <div class="flex max-w-[1100px] flex-col gap-6">
        <template v-if="run.status === 'uploaded'">
            <AdminSection
                title="1. Match the columns"
                :description="`Your file has ${run.total_rows.toLocaleString('en-GB')} rows. We matched the columns by their names; change any that are wrong.`"
            >
                <div class="grid gap-4">
                    <div
                        v-for="field in fields"
                        :key="field.key"
                        class="grid items-start gap-2 sm:grid-cols-[minmax(0,14rem)_minmax(0,22rem)] sm:gap-6"
                    >
                        <div>
                            <Label :for="`map-${field.key}`">
                                {{ field.label }}
                                <span
                                    v-if="field.required"
                                    class="text-destructive"
                                    >*<span class="sr-only">
                                        (required)</span
                                    ></span
                                >
                            </Label>
                            <p
                                v-if="field.hint"
                                class="mt-1 text-xs text-muted-foreground"
                            >
                                {{ field.hint }}
                            </p>
                        </div>
                        <Select
                            :model-value="chosen[field.key]"
                            :disabled="!canEdit"
                            @update:model-value="
                                (value) => pick(field.key, value)
                            "
                        >
                            <SelectTrigger
                                :id="`map-${field.key}`"
                                class="w-full"
                            >
                                <SelectValue placeholder="Not in my file" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem :value="NONE">
                                    Not in my file
                                </SelectItem>
                                <SelectItem
                                    v-for="(header, columnIndex) in columns"
                                    :key="columnIndex"
                                    :value="String(columnIndex)"
                                >
                                    {{ columnLabel(columnIndex) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <InputError :message="startErrors.mapping" />
                </div>
            </AdminSection>

            <AdminSection
                :title="`2. Check the first ${limits.preview_rows} rows`"
                description="Nothing is saved yet. This is what would happen to each row."
            >
                <p
                    v-if="missing.length > 0"
                    class="text-sm text-muted-foreground"
                >
                    Choose a column for
                    {{ missing.map((field) => field.label).join(' and ') }} to
                    see the preview.
                </p>
                <template v-else-if="preview">
                    <p class="mb-4 text-sm text-muted-foreground">
                        Of these {{ preview.rows.length }} rows:
                        {{ preview.counts.create }} will be added,
                        {{ preview.counts.update }} updated,
                        {{ preview.counts.skip }} skipped,
                        {{ preview.counts.fail }}
                        {{ preview.counts.fail === 1 ? 'has' : 'have' }} a
                        problem. Rows with a problem are left out and listed in
                        a file you can fix and upload again.
                    </p>
                    <div class="overflow-x-auto rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead class="w-16">Row</TableHead>
                                    <TableHead>Result</TableHead>
                                    <TableHead
                                        v-for="field in mappedFields"
                                        :key="field.key"
                                    >
                                        {{ field.label }}
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="row in preview.rows"
                                    :key="row.number"
                                >
                                    <TableCell class="tabular-nums">{{
                                        row.number
                                    }}</TableCell>
                                    <TableCell class="min-w-44">
                                        <StatusBadge
                                            :label="row.label"
                                            :tone="outcomeTone[row.outcome]"
                                        />
                                        <p
                                            v-if="row.message"
                                            class="mt-1 max-w-72 text-xs whitespace-normal text-muted-foreground"
                                        >
                                            {{ row.message }}
                                        </p>
                                    </TableCell>
                                    <TableCell
                                        v-for="field in mappedFields"
                                        :key="field.key"
                                        class="max-w-56 truncate"
                                    >
                                        {{ row.values[field.key] }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </template>
            </AdminSection>

            <AdminSection
                title="3. Import"
                description="The import runs in the background. You can leave this page; you get a notification when it is finished."
            >
                <div class="grid gap-5">
                    <div
                        v-for="option in options"
                        :key="option.key"
                        class="grid gap-1"
                    >
                        <div class="flex items-start gap-3">
                            <Checkbox
                                :id="`confirm-${option.key}`"
                                :model-value="confirmed[option.key] ?? false"
                                class="mt-0.5"
                                :aria-invalid="
                                    !!startErrors[option.key] || undefined
                                "
                                @update:model-value="
                                    (value) =>
                                        (confirmed = {
                                            ...confirmed,
                                            [option.key]: value === true,
                                        })
                                "
                            />
                            <Label
                                :for="`confirm-${option.key}`"
                                class="text-sm leading-5 font-normal"
                                >{{ option.label }}</Label
                            >
                        </div>
                        <InputError :message="startErrors[option.key]" />
                    </div>
                    <InputError :message="startErrors.run" />

                    <div class="flex flex-wrap items-center gap-3">
                        <Button
                            type="button"
                            :loading="starting"
                            :disabled="startReason !== null"
                            :title="startReason ?? undefined"
                            @click="startImport"
                        >
                            <template #icon
                                ><Play aria-hidden="true"
                            /></template>
                            Import {{ run.total_rows.toLocaleString('en-GB') }}
                            rows
                        </Button>
                        <Button
                            v-if="canEdit"
                            type="button"
                            variant="ghost"
                            @click="cancel"
                        >
                            Cancel
                        </Button>
                        <p
                            v-if="startReason"
                            class="text-sm text-muted-foreground"
                        >
                            {{ startReason }}
                        </p>
                    </div>
                </div>
            </AdminSection>
        </template>

        <AdminSection
            v-else-if="run.working"
            title="Importing"
            description="This runs in the background, a few seconds at a time. You can leave this page."
        >
            <div class="grid gap-3">
                <progress
                    class="h-2 w-full overflow-hidden rounded-full [&::-moz-progress-bar]:bg-primary [&::-webkit-progress-bar]:bg-muted [&::-webkit-progress-value]:bg-primary"
                    :value="run.processed_rows"
                    :max="Math.max(run.total_rows, 1)"
                    aria-label="Import progress"
                ></progress>
                <p class="text-sm text-muted-foreground" aria-live="polite">
                    {{ run.processed_rows.toLocaleString('en-GB') }} of
                    {{ run.total_rows.toLocaleString('en-GB') }} rows ({{
                        percent
                    }}%) ·
                    {{
                        run.status === 'queued'
                            ? 'waiting for a worker to pick it up'
                            : 'running'
                    }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Nothing happens until the queue worker runs: on the live
                    site that is the scheduler (every minute). On your computer,
                    run
                    <code>php artisan queue:work</code>.
                </p>
            </div>
        </AdminSection>

        <template v-else>
            <AdminSection
                :title="
                    run.status === 'finished'
                        ? 'Report'
                        : 'The import did not finish'
                "
                :description="
                    run.finished_at
                        ? `Finished ${formatDateTime(run.finished_at)}`
                        : undefined
                "
            >
                <p
                    v-if="run.message"
                    role="alert"
                    class="mb-4 text-sm text-error-700"
                >
                    {{ run.message }}
                </p>
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div class="rounded-lg border p-4">
                        <dt class="text-xs text-muted-foreground">Added</dt>
                        <dd class="mt-1 text-2xl font-medium tabular-nums">
                            {{ run.created.toLocaleString('en-GB') }}
                        </dd>
                    </div>
                    <div class="rounded-lg border p-4">
                        <dt class="text-xs text-muted-foreground">Updated</dt>
                        <dd class="mt-1 text-2xl font-medium tabular-nums">
                            {{ run.updated.toLocaleString('en-GB') }}
                        </dd>
                    </div>
                    <div class="rounded-lg border p-4">
                        <dt class="text-xs text-muted-foreground">
                            Skipped (already there)
                        </dt>
                        <dd class="mt-1 text-2xl font-medium tabular-nums">
                            {{ run.skipped.toLocaleString('en-GB') }}
                        </dd>
                    </div>
                    <div class="rounded-lg border p-4">
                        <dt class="text-xs text-muted-foreground">
                            With a problem
                        </dt>
                        <dd
                            class="mt-1 text-2xl font-medium tabular-nums"
                            :class="run.failed > 0 ? 'text-error-700' : ''"
                        >
                            {{ run.failed.toLocaleString('en-GB') }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 flex flex-wrap items-center gap-3">
                    <Button v-if="run.errors_url" as-child variant="outline">
                        <a :href="run.errors_url">
                            <Download aria-hidden="true" />
                            Download the rows with a problem (CSV)
                        </a>
                    </Button>
                    <Button as-child>
                        <Link :href="run.list_url"
                            >View {{ run.type_label }}</Link
                        >
                    </Button>
                    <Button as-child variant="ghost">
                        <Link :href="index.url()">Import another file</Link>
                    </Button>
                </div>
                <p
                    v-if="run.errors_url"
                    class="mt-3 max-w-prose text-xs text-muted-foreground"
                >
                    The problem file has your columns first and the reason last.
                    Fix the rows and upload it again; the reason column is
                    ignored. It is kept for 7 days.
                </p>
            </AdminSection>
        </template>
    </div>
</template>
