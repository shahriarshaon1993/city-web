<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { FileUp } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { importMethod } from '@/routes/admin/redirects';
import type { RedirectImportReport } from '@/types';

// SEO-TEC-008 CSV import. The file is checked here first (type and size, VAL-03), then every row
// on the server; the dialog stays open to list the rows that were skipped (TOAST-06 "View why").
const MAX_BYTES = 2 * 1024 * 1024;

const open = defineModel<boolean>('open', { default: false });

const file = ref<File | null>(null);
const error = ref<string | null>(null);
const processing = ref(false);
const report = ref<RedirectImportReport | null>(null);
const input = ref<HTMLInputElement | null>(null);

watch(open, (isOpen) => {
    if (isOpen) {
        file.value = null;
        error.value = null;
        report.value = null;
    }
});

function choose(event: Event): void {
    const chosen = (event.target as HTMLInputElement).files?.[0] ?? null;

    error.value = null;
    file.value = null;

    if (!chosen) {
        return;
    }

    if (!/\.(csv|txt)$/i.test(chosen.name)) {
        error.value = 'The file must be a CSV (.csv).';

        return;
    }

    if (chosen.size > MAX_BYTES) {
        error.value = 'The file must be 2 MB or smaller.';

        return;
    }

    file.value = chosen;
}

function submit(): void {
    if (!file.value) {
        error.value = 'Choose a CSV file to import.';

        return;
    }

    router.post(
        importMethod.url(),
        { file: file.value },
        {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => {
                processing.value = true;
            },
            onFinish: () => {
                processing.value = false;
            },
            onError: (errors) => {
                error.value = errors.file ?? 'The file could not be imported.';
            },
            onSuccess: (page) => {
                const flashed = (
                    page as unknown as {
                        flash?: { import_report?: RedirectImportReport };
                    }
                ).flash?.import_report;

                report.value = flashed ?? null;

                if (!flashed || flashed.skipped === 0) {
                    open.value = false;
                }
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="grid gap-5" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>Import redirects</DialogTitle>
                    <DialogDescription>
                        Upload a CSV with the columns Source, Target, Code and
                        Active — the file the export writes. An address that
                        already has a rule updates it. Use /old/* in Source for
                        a prefix rule.
                    </DialogDescription>
                </DialogHeader>

                <template v-if="report">
                    <p class="text-sm" role="status">
                        {{ report.created }} new, {{ report.updated }} updated,
                        {{ report.skipped }} skipped.
                    </p>
                    <ul
                        class="max-h-56 divide-y overflow-y-auto rounded-md border text-sm"
                        aria-label="Rows that were skipped"
                    >
                        <li
                            v-for="item in report.errors.slice(0, 50)"
                            :key="item.row"
                            class="grid gap-0.5 px-3 py-2"
                        >
                            <span class="text-xs text-muted-foreground"
                                >Row {{ item.row }} ·
                                <span class="font-mono">{{
                                    item.source
                                }}</span></span
                            >
                            <span>{{ item.message }}</span>
                        </li>
                    </ul>
                    <p
                        v-if="report.errors.length > 50"
                        class="text-xs text-muted-foreground"
                    >
                        … and {{ report.errors.length - 50 }} more.
                    </p>
                    <DialogFooter>
                        <Button type="button" @click="open = false"
                            >Done</Button
                        >
                    </DialogFooter>
                </template>

                <template v-else>
                    <div class="grid gap-2">
                        <Label for="redirect-file">CSV file</Label>
                        <input
                            id="redirect-file"
                            ref="input"
                            type="file"
                            accept=".csv,text/csv"
                            class="block w-full cursor-pointer rounded-md border border-input bg-background text-sm file:mr-3 file:cursor-pointer file:border-0 file:bg-muted file:px-3 file:py-2 file:text-sm file:font-medium"
                            aria-describedby="redirect-file-help"
                            :aria-invalid="!!error || undefined"
                            @change="choose"
                        />
                        <p
                            id="redirect-file-help"
                            class="text-xs text-muted-foreground"
                        >
                            .csv, up to 2 MB. The whole file is read; rows that
                            fail are skipped and listed.
                        </p>
                        <InputError :message="error ?? undefined" />
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="open = false"
                            >Cancel</Button
                        >
                        <Button type="submit" :disabled="processing || !file">
                            <Spinner v-if="processing" />
                            <FileUp v-else aria-hidden="true" />
                            Import
                        </Button>
                    </DialogFooter>
                </template>
            </form>
        </DialogContent>
    </Dialog>
</template>
