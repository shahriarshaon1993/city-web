<script setup lang="ts">
import { useEventListener, useNow } from '@vueuse/core';
import { computed, nextTick, ref, useTemplateRef, watch } from 'vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import ErrorSummary from '@/components/forms/ErrorSummary.vue';
import { Spinner } from '@/components/ui/spinner';
import { useUnsavedGuard } from '@/composables/useUnsavedGuard';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { cn } from '@/lib/utils';
import { showToast } from '@/lib/notify';

// CMP-ADM-FORM / CRUD-04 / UX-10: the layout of every full-page editor. Sections sit in the
// default slot; the primary action lives in a sticky rail (≥ 1280 px) or a sticky bottom bar
// (below). It adds the dirty marker on the title, "Last saved …", Ctrl/⌘+S, the error summary
// (TOAST-03: validation never toasts) and the unsaved-changes guard.
const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        /** The page's `useForm()` (or any object with these fields). */
        form: {
            isDirty: boolean;
            processing: boolean;
            hasErrors: boolean;
            errors: Record<string, string | undefined>;
            recentlySuccessful: boolean;
        };
        /** ISO time of the last save, from the server (or the last autosave). */
        lastSaved?: string | null;
        /** One short line under "Last saved", e.g. "Autosaved" (UX-10). */
        note?: string | null;
        saveLabel?: string;
        /** Overrides the default reason for the disabled save button. */
        saveReason?: string | null;
        /** False: no right rail; the page body gets the full width and the sticky bar shows at every width (the page builder). */
        rail?: boolean;
        /** Maps an error key to the id of its field, for the summary links. */
        fieldId?: (key: string) => string;
    }>(),
    { saveLabel: 'Save changes', lastSaved: null, rail: true },
);

const emit = defineEmits<{ save: []; reveal: [id: string] }>();

useUnsavedGuard(() => props.form.isDirty);

// --- Last saved ---------------------------------------------------------------------------------
const savedAt = ref<string | null>(props.lastSaved);
const now = useNow({ interval: 30_000 });

watch(
    () => props.lastSaved,
    (value) => {
        savedAt.value = value;
    },
);

watch(
    () => props.form.recentlySuccessful,
    (saved) => {
        if (saved) {
            savedAt.value = new Date().toISOString();
        }
    },
);

const savedText = computed(() =>
    savedAt.value ? timeAgo(savedAt.value, now.value.getTime()) : null,
);

// --- Save ---------------------------------------------------------------------------------------
const reason = computed(() => {
    if (props.saveReason) {
        return props.saveReason;
    }

    return props.form.isDirty ? null : 'There are no changes to save.';
});

function save(): void {
    if (props.form.processing) {
        return;
    }

    if (!props.form.isDirty && !props.saveReason) {
        showToast({ type: 'info', title: 'No changes to save' });

        return;
    }

    emit('save');
}

// HELP-09: Ctrl/⌘+S saves, wherever the focus is.
useEventListener(window, 'keydown', (event: KeyboardEvent) => {
    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 's') {
        event.preventDefault();
        save();
    }
});

// --- Error summary (CMP-FORM-01) ---------------------------------------------------------------------
const summary = useTemplateRef<InstanceType<typeof ErrorSummary>>('summary');
const items = computed(() =>
    Object.entries(props.form.errors)
        .filter(([, message]) => !!message)
        .map(([key, message]) => ({
            id: props.fieldId ? props.fieldId(key) : key,
            message: String(message),
        })),
);

watch(
    () => props.form.hasErrors,
    async (has) => {
        if (has) {
            await nextTick();
            summary.value?.focus();
        }
    },
);
</script>

<template>
    <AdminPageHeader
        :title="title"
        :description="description"
        :dirty="form.isDirty"
    >
        <template v-if="$slots.actions" #actions
            ><slot name="actions"
        /></template>
        <template v-if="$slots.tabs" #tabs><slot name="tabs" /></template>
    </AdminPageHeader>

    <div
        :class="
            rail &&
            'gap-8 xl:grid xl:grid-cols-[minmax(0,1fr)_18rem] xl:items-start'
        "
    >
        <div class="flex min-w-0 flex-col gap-6">
            <ErrorSummary
                v-if="items.length"
                ref="summary"
                :items="items"
                @navigate="(id: string) => emit('reveal', id)"
            />
            <slot />

            <!-- < 1280 px: sticky bottom bar -->
            <div
                :class="
                    cn(
                        'sticky bottom-0 z-20 -mx-4 flex items-center justify-between gap-3 border-t bg-background/95 px-4 py-3 backdrop-blur md:-mx-6 md:px-6',
                        rail && 'xl:hidden',
                    )
                "
            >
                <p
                    class="min-w-0 truncate text-xs text-muted-foreground"
                    :title="savedAt ? formatDateTime(savedAt) : undefined"
                >
                    <template v-if="form.isDirty">Unsaved changes</template>
                    <template v-else-if="savedText"
                        >Last saved {{ savedText }}</template
                    >
                    <template v-if="note"> · {{ note }}</template>
                </p>
                <div class="flex shrink-0 items-center gap-2">
                    <slot name="bar" />
                    <ReasonButton
                        type="button"
                        :reason="form.processing ? 'Saving…' : reason"
                        @click="save"
                    >
                        <Spinner v-if="form.processing" />
                        {{ saveLabel }}
                    </ReasonButton>
                </div>
            </div>
        </div>

        <!-- ≥ 1280 px: sticky action rail -->
        <aside
            v-if="rail"
            class="sticky top-20 hidden max-h-[calc(100dvh-6rem)] flex-col gap-4 overflow-y-auto overscroll-contain rounded-xl border bg-card p-5 xl:flex"
            aria-label="Actions"
        >
            <ReasonButton
                type="button"
                class="w-full"
                :reason="form.processing ? 'Saving…' : reason"
                @click="save"
            >
                <Spinner v-if="form.processing" />
                {{ saveLabel }}
            </ReasonButton>
            <p
                class="text-xs text-muted-foreground"
                :title="savedAt ? formatDateTime(savedAt) : undefined"
            >
                <template v-if="form.isDirty">
                    <span class="font-medium text-foreground"
                        >Unsaved changes</span
                    >
                    · press Ctrl/⌘+S to save
                </template>
                <template v-else-if="savedText"
                    >Last saved {{ savedText }}</template
                >
                <template v-else>Not saved yet</template>
                <span v-if="note" class="block" role="status">{{ note }}</span>
            </p>
            <slot name="rail" />
        </aside>
    </div>
</template>
