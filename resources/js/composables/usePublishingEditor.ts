import type { InertiaForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { useIntervalFn } from '@vueuse/core';
import { onBeforeUnmount, ref, watch } from 'vue';
import { HttpError, sendJson } from '@/lib/http';
import type { ConflictDetails, WorkflowState } from '@/types';

// The editor side of the publishing workflow for any entity form (CMS-PUB-006, UX-10):
// - sends `lock_version` with every save, and opens the conflict dialog when the server answers that
//   someone else saved first (flash `conflict`); "Overwrite" (Admin) resubmits with `overwrite`;
// - autosaves the form every 60 seconds while it is dirty — to the pending revision of a published
//   item, to the draft otherwise — silently: no toast, and never publishes. A form with validation
//   errors is skipped until it changes again, so autosave never gets in the way of typing.
export const AUTOSAVE_MS = 60_000;

type Options = {
    form: InertiaForm<any>;
    /** The workflow block of the saved item, or null while it is a new, unsaved one. */
    workflow: () => WorkflowState | null;
    /** The server's last-saved time (the page prop). */
    lastSaved: () => string | null;
    /** The autosave URL of the saved item. */
    autosaveUrl: () => string | null;
    canEdit: () => boolean;
    /** Put the server's values back into the form (after "Reload"). */
    reseed: () => void;
};

export function usePublishingEditor(options: Options) {
    const { form } = options;

    const lockVersion = ref<number | null>(
        options.workflow()?.lock_version ?? null,
    );
    const savedAt = ref<string | null>(options.lastSaved());
    const conflict = ref<ConflictDetails | null>(null);
    const conflictOpen = ref(false);
    const autosaveNote = ref<string | null>(null);
    const autosaving = ref(false);
    let skippedSignature: string | null = null;

    // Every save, autosave or workflow verb returns fresh props.
    watch(
        () => options.workflow()?.lock_version ?? null,
        (value) => {
            lockVersion.value = value;
        },
    );
    watch(options.lastSaved, (value) => {
        savedAt.value = value;
    });

    /** Add the lock (and the Overwrite choice) to what the next save sends. */
    function prepare(overwrite = false): void {
        form.transform((data: Record<string, unknown>) => ({
            ...data,
            lock_version: lockVersion.value,
            ...(overwrite ? { overwrite: true } : {}),
        }));
    }

    const stop = router.on('flash', (event) => {
        const found = (event as CustomEvent).detail?.flash?.conflict as
            | ConflictDetails
            | undefined;

        if (found) {
            conflict.value = found;
            conflictOpen.value = true;
        }
    });

    onBeforeUnmount(stop);

    function showConflict(details: ConflictDetails): void {
        conflict.value = details;
        conflictOpen.value = true;
    }

    /** "Overwrite": the same save again, with the Admin-only flag. */
    function overwrite(save: (overwrite: boolean) => void): void {
        conflictOpen.value = false;
        save(true);
    }

    /** "Reload": the server's version replaces what was typed. */
    function reload(): void {
        conflictOpen.value = false;
        router.reload({
            onSuccess: () => {
                options.reseed();
                form.clearErrors();
            },
        });
    }

    async function autosave(): Promise<void> {
        const url = options.autosaveUrl();

        if (
            !url ||
            !options.canEdit() ||
            !form.isDirty ||
            form.processing ||
            autosaving.value ||
            conflictOpen.value
        ) {
            return;
        }

        const sent = JSON.parse(JSON.stringify(form.data())) as Record<
            string,
            unknown
        >;
        const signature = JSON.stringify(sent);

        if (signature === skippedSignature) {
            return;
        }

        autosaving.value = true;
        autosaveNote.value = 'Autosaving…';

        try {
            const result = await sendJson<{
                pending: boolean;
                lock_version: number;
                saved_at: string | null;
            }>('PATCH', url, { ...sent, lock_version: lockVersion.value });

            lockVersion.value = result.lock_version;
            savedAt.value = result.saved_at;
            skippedSignature = null;
            // What was sent is saved: the unsaved-changes guard clears unless more was typed meanwhile.
            form.defaults(sent);
            autosaveNote.value = result.pending
                ? 'Autosaved to pending changes'
                : 'Autosaved';
        } catch (error) {
            if (error instanceof HttpError && error.status === 409) {
                const details = (
                    error.body as { conflict?: ConflictDetails } | null
                )?.conflict;

                if (details) {
                    showConflict(details);
                }

                autosaveNote.value = null;
            } else if (error instanceof HttpError && error.status === 422) {
                skippedSignature = signature;
                autosaveNote.value =
                    'Autosave paused: fix the highlighted fields.';
            } else {
                // Offline or a server error: try again at the next tick.
                autosaveNote.value = null;
            }
        } finally {
            autosaving.value = false;
        }
    }

    useIntervalFn(() => void autosave(), AUTOSAVE_MS);

    return {
        lockVersion,
        savedAt,
        conflict,
        conflictOpen,
        autosaveNote,
        prepare,
        overwrite,
        reload,
    };
}
