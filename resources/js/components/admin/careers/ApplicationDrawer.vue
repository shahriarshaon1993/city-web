<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    Download,
    ExternalLink,
    Mail,
    MessageCircle,
    Phone,
    Trash2,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import FormField from '@/components/forms/FormField.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { formatFileSize, formatPhone } from '@/lib/format';
import { HttpError, sendJson } from '@/lib/http';
import { destroy, notes, show, status } from '@/routes/admin/applications';
import type { ApplicationStatusValue, JobApplicationDetail } from '@/types';

// ADM-SCR-10 application drawer (FR-CAR-005): who applied and for what, the pipeline status, the CV
// download, the cover letter and the HR team's internal notes. The data is fetched as JSON when an
// application is opened and again after every change, so the drawer never shows a stale status. The
// CV link is an authorised route, never a file path. Closing reports to the page, which drops
// `?application=` from the address.
const props = defineProps<{
    applicationId: number | null;
    /** The application the page loaded with `?application={id}`, so a pasted link opens at once. */
    initial: JobApplicationDetail | null;
}>();

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ changed: [] }>();

const detail = ref<JobApplicationDetail | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const notesText = ref('');
const notesSaving = ref(false);
const notesError = ref<string | undefined>(undefined);
const deleteOpen = ref(false);
const deleting = ref(false);

let requestId = 0;

async function load(): Promise<void> {
    if (props.applicationId === null) {
        return;
    }

    const current = ++requestId;
    loading.value = detail.value?.id !== props.applicationId;
    error.value = null;

    try {
        const response = await sendJson<{ data: JobApplicationDetail }>(
            'GET',
            show.url(props.applicationId),
        );

        if (current === requestId) {
            detail.value = response.data;
        }
    } catch (caught) {
        if (current === requestId) {
            detail.value = null;
            error.value =
                caught instanceof HttpError && caught.status === 403
                    ? 'You do not have access to this application.'
                    : caught instanceof HttpError && caught.status === 404
                      ? 'This application no longer exists. It may have been deleted.'
                      : 'The application could not be loaded. Check your connection and try again.';
        }
    } finally {
        if (current === requestId) {
            loading.value = false;
        }
    }
}

watch(
    () => [open.value, props.applicationId] as const,
    ([isOpen, id]) => {
        if (!isOpen || id === null) {
            return;
        }

        if (props.initial?.id === id) {
            detail.value = props.initial;
        } else {
            detail.value = null;
        }

        void load();
    },
    { immediate: true },
);

// The notes box follows the loaded application, but never overwrites what is being typed for the same one.
watch(
    () => detail.value?.id,
    () => {
        notesText.value = detail.value?.notes ?? '';
        notesError.value = undefined;
    },
);

function changed(): void {
    void load();
    emit('changed');
}

function moveTo(value: string | number | null): void {
    if (!detail.value || value === null || value === detail.value.status) {
        return;
    }

    router.patch(
        status.url(detail.value.id),
        { status: value },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: changed,
        },
    );
}

function saveNotes(): void {
    if (!detail.value) {
        return;
    }

    notesSaving.value = true;
    notesError.value = undefined;

    router.patch(
        notes.url(detail.value.id),
        { notes: notesText.value },
        {
            preserveScroll: true,
            preserveState: true,
            onError: (errors) => {
                notesError.value = errors.notes;
            },
            onFinish: () => {
                notesSaving.value = false;
                changed();
            },
        },
    );
}

function confirmDelete(): void {
    if (!detail.value) {
        return;
    }

    deleting.value = true;
    router.delete(destroy.url(detail.value.id), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            deleteOpen.value = false;
            open.value = false;
        },
        onFinish: () => {
            deleting.value = false;
        },
    });
}

const tone: Record<ApplicationStatusValue, string> = {
    new: 'bg-warning-50 text-warning-700',
    shortlisted: 'bg-info-50 text-info-700',
    interview: 'bg-info-50 text-info-700',
    hired: 'bg-success-50 text-success-700',
    rejected: 'bg-stone-100 text-stone-700',
};
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="w-full gap-0 overflow-hidden p-0 sm:max-w-[560px]">
            <SheetHeader class="border-b p-5 pr-12">
                <SheetTitle class="font-display text-xl font-medium">
                    {{ detail?.name ?? 'Application' }}
                </SheetTitle>
                <SheetDescription>
                    <template v-if="detail">
                        {{ detail.job?.title ?? 'General CV' }} · received
                        <time
                            v-if="detail.created_at"
                            :datetime="detail.created_at"
                            >{{ formatDateTime(detail.created_at) }}</time
                        >
                    </template>
                    <template v-else>Application details</template>
                </SheetDescription>
            </SheetHeader>

            <div
                v-if="loading"
                class="grid gap-3 p-5"
                role="status"
                aria-label="Loading the application"
            >
                <Skeleton class="h-9 w-full" />
                <Skeleton class="h-24 w-full" />
                <Skeleton class="h-24 w-full" />
            </div>

            <div v-else-if="error" class="grid gap-3 p-5" role="alert">
                <p class="text-sm">{{ error }}</p>
                <div>
                    <Button variant="outline" size="sm" @click="load"
                        >Try again</Button
                    >
                </div>
            </div>

            <div
                v-else-if="detail"
                class="flex min-h-0 flex-1 flex-col overflow-y-auto"
            >
                <div class="grid gap-4 border-b p-5">
                    <div class="grid gap-2">
                        <Label for="application-status">Status</Label>
                        <AdminSelect
                            id="application-status"
                            :model-value="detail.status"
                            :options="detail.statuses"
                            :disabled="!detail.can.update"
                            @update:model-value="moveTo"
                        />
                        <p
                            v-if="!detail.can.update"
                            class="text-xs text-muted-foreground"
                        >
                            You can view this application but not change it.
                        </p>
                    </div>

                    <p
                        v-if="detail.needs_review"
                        class="rounded-md bg-warning-50 px-3 py-2 text-sm text-warning-700"
                        role="note"
                    >
                        <span class="font-medium">Needs review.</span>
                        {{ detail.review_label }}
                    </p>

                    <div class="flex flex-wrap gap-2">
                        <Button as-child size="sm">
                            <a :href="detail.cv.url" download>
                                <Download aria-hidden="true" />
                                Download CV
                                <span class="text-xs opacity-80">{{
                                    formatFileSize(detail.cv.size)
                                }}</span>
                            </a>
                        </Button>
                        <Button as-child variant="outline" size="sm">
                            <a :href="detail.links.call">
                                <Phone aria-hidden="true" />
                                Call
                            </a>
                        </Button>
                        <Button
                            v-if="detail.links.whatsapp"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <a
                                :href="detail.links.whatsapp"
                                target="_blank"
                                rel="noopener"
                            >
                                <MessageCircle aria-hidden="true" />
                                WhatsApp
                            </a>
                        </Button>
                        <Button as-child variant="outline" size="sm">
                            <a :href="detail.links.email">
                                <Mail aria-hidden="true" />
                                Email
                            </a>
                        </Button>
                    </div>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ detail.cv.name }}
                    </p>
                </div>

                <div class="grid gap-6 p-5">
                    <dl
                        class="grid grid-cols-[8.5rem_minmax(0,1fr)] gap-x-4 gap-y-3 text-sm"
                    >
                        <dt class="text-muted-foreground">Status</dt>
                        <dd>
                            <span
                                :class="`inline-flex rounded-full px-2 py-0.5 text-xs font-medium ${tone[detail.status]}`"
                                >{{
                                    detail.statuses.find(
                                        (item) => item.value === detail?.status,
                                    )?.label
                                }}</span
                            >
                        </dd>
                        <dt class="text-muted-foreground">Phone</dt>
                        <dd class="tabular-nums">
                            {{ formatPhone(detail.phone) }}
                        </dd>
                        <dt class="text-muted-foreground">Email</dt>
                        <dd class="break-all">{{ detail.email }}</dd>
                        <dt class="text-muted-foreground">Role</dt>
                        <dd>
                            <a
                                v-if="detail.job?.edit_url"
                                :href="detail.job.edit_url"
                                class="inline-flex items-center gap-1 text-primary underline-offset-4 hover:underline"
                            >
                                {{ detail.job.title }}
                                <ExternalLink
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </a>
                            <template v-else>{{
                                detail.job?.title ?? 'General CV (no role)'
                            }}</template>
                        </dd>
                        <template v-if="detail.current_position">
                            <dt class="text-muted-foreground">
                                Current position
                            </dt>
                            <dd>{{ detail.current_position }}</dd>
                        </template>
                        <template v-if="detail.years_experience !== null">
                            <dt class="text-muted-foreground">Experience</dt>
                            <dd>
                                {{ detail.years_experience }}
                                {{
                                    detail.years_experience === 1
                                        ? 'year'
                                        : 'years'
                                }}
                            </dd>
                        </template>
                        <template v-if="detail.expected_salary">
                            <dt class="text-muted-foreground">
                                Expected salary
                            </dt>
                            <dd>{{ detail.expected_salary }}</dd>
                        </template>
                        <template v-if="detail.linkedin_url">
                            <dt class="text-muted-foreground">LinkedIn</dt>
                            <dd class="min-w-0">
                                <a
                                    :href="detail.linkedin_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex max-w-full items-center gap-1 break-all text-primary underline-offset-4 hover:underline"
                                >
                                    {{ detail.linkedin_url }}
                                    <ExternalLink
                                        class="size-3.5 shrink-0"
                                        aria-hidden="true"
                                    />
                                </a>
                            </dd>
                        </template>
                    </dl>

                    <section v-if="detail.cover_letter" class="grid gap-1.5">
                        <h3 class="text-sm font-medium">Cover letter</h3>
                        <p
                            class="rounded-md bg-muted/50 px-3 py-2 text-sm break-words whitespace-pre-line"
                        >
                            {{ detail.cover_letter }}
                        </p>
                    </section>

                    <form
                        v-if="detail.can.update"
                        class="grid gap-3"
                        @submit.prevent="saveNotes"
                    >
                        <FormField
                            id="application-notes"
                            label="Notes"
                            helper="Internal: only your team sees notes, never the applicant."
                            :counter="{ length: notesText.length, max: 5000 }"
                            :error="notesError"
                        >
                            <template #default="{ id, describedby, invalid }">
                                <Textarea
                                    :id="id"
                                    v-model="notesText"
                                    rows="5"
                                    maxlength="5000"
                                    :aria-invalid="invalid || undefined"
                                    :aria-describedby="describedby"
                                />
                            </template>
                        </FormField>
                        <div>
                            <Button
                                type="submit"
                                size="sm"
                                :disabled="
                                    notesSaving ||
                                    notesText.trim() === (detail.notes ?? '')
                                "
                            >
                                <Spinner v-if="notesSaving" />
                                Save notes
                            </Button>
                        </div>
                    </form>
                    <section v-else-if="detail.notes" class="grid gap-1.5">
                        <h3 class="text-sm font-medium">Notes</h3>
                        <p class="text-sm whitespace-pre-line">
                            {{ detail.notes }}
                        </p>
                    </section>

                    <section class="grid gap-1.5">
                        <h3 class="text-sm font-medium">Consent given</h3>
                        <p class="text-sm text-muted-foreground">
                            {{
                                detail.consent_text ||
                                'No consent text was stored.'
                            }}
                        </p>
                    </section>

                    <p
                        v-if="detail.removal_at"
                        class="text-xs text-muted-foreground"
                    >
                        Last updated
                        {{
                            timeAgo(
                                detail.updated_at ?? detail.created_at ?? '',
                            )
                        }}. Unless someone changes it, this application and its
                        CV are removed on
                        {{ formatDateTime(detail.removal_at).split(' · ')[0] }}.
                    </p>
                </div>

                <div
                    v-if="detail.can.delete"
                    class="mt-auto flex items-center justify-between gap-3 border-t p-5"
                >
                    <p class="text-xs text-muted-foreground">
                        Deleting moves the application to the Recycle bin for
                        365 days.
                    </p>
                    <Button
                        variant="outline"
                        size="sm"
                        class="text-destructive"
                        @click="deleteOpen = true"
                    >
                        <Trash2 aria-hidden="true" />
                        Delete
                    </Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>

    <ConfirmDialog
        v-model:open="deleteOpen"
        :title="`Delete ${detail?.name ?? 'this application'}?`"
        description="The application and its CV move to the Recycle bin. You can restore them for 365 days."
        confirm-label="Delete application"
        destructive
        :processing="deleting"
        @confirm="confirmDelete"
    />
</template>
