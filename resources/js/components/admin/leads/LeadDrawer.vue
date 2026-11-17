<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    CalendarClock,
    Download,
    ExternalLink,
    Mail,
    MessageCircle,
    Phone,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import AdminSelect from '@/components/admin/catalog/AdminSelect.vue';
import RichTextField from '@/components/admin/catalog/RichTextField.vue';
import ConfirmDialog from '@/components/admin/ConfirmDialog.vue';
import DateTimePicker from '@/components/forms/DateTimePicker.vue';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { leadChanged, leadsVersion } from '@/composables/useLeadActions';
import { dhakaInputValue, formatDateTime, timeAgo } from '@/lib/admin-format';
import { HttpError, sendJson } from '@/lib/http';
import { assign, destroy, followUp, show } from '@/routes/admin/leads';
import { store as storeNote } from '@/routes/admin/leads/notes';
import type {
    LeadDetail,
    LeadPerson,
    LeadStatusOption,
    LeadStatusValue,
} from '@/types';
import LeadTimeline from './LeadTimeline.vue';

// CMP-ADM-LEAD / FR-LEAD-009: the lead drawer. Header: name, status and owner, call / WhatsApp /
// email. Tabs: Details (answers, campaign, files), Timeline, Notes (rich text, internal) and
// Follow-up. The data is fetched as JSON when a lead is opened and again after every change, so the
// drawer never shows a stale status. Closing reports to the page, which drops `?lead=` from the URL.
const props = defineProps<{
    leadId: number | null;
    /** The lead the page loaded with `?lead={id}`, so a pasted link opens at once. */
    initial: LeadDetail | null;
    statuses: LeadStatusOption[];
    assignees: LeadPerson[];
    canAssign: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{ move: [lead: LeadDetail, to: LeadStatusValue] }>();

const detail = ref<LeadDetail | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);
const tab = ref('details');

let requestId = 0;

async function load(): Promise<void> {
    if (props.leadId === null) {
        return;
    }

    const current = ++requestId;
    loading.value = detail.value?.id !== props.leadId;
    error.value = null;

    try {
        const response = await sendJson<{ data: LeadDetail }>(
            'GET',
            show.url(props.leadId),
        );

        if (current === requestId) {
            detail.value = response.data;
        }
    } catch (caught) {
        if (current === requestId) {
            detail.value = null;
            error.value =
                caught instanceof HttpError && caught.status === 403
                    ? 'You do not have access to this lead.'
                    : caught instanceof HttpError && caught.status === 404
                      ? 'This lead no longer exists. It may have been deleted.'
                      : 'The lead could not be loaded. Check your connection and try again.';
        }
    } finally {
        if (current === requestId) {
            loading.value = false;
        }
    }
}

watch(
    () => [open.value, props.leadId] as const,
    ([isOpen, id]) => {
        if (!isOpen || id === null) {
            return;
        }

        tab.value = 'details';

        if (props.initial?.id === id) {
            detail.value = props.initial;
            // Still refresh: the timeline may have moved on since the page was built.
            void load();
        } else {
            detail.value = null;
            void load();
        }
    },
    { immediate: true },
);

watch(leadsVersion, () => {
    if (open.value) {
        void load();
    }
});

// --- owner ---------------------------------------------------------------------------------------
const owners = computed(() =>
    props.assignees.map((person) => ({
        value: person.id,
        label: person.name,
    })),
);

function reassign(value: number | null): void {
    if (!detail.value || value === (detail.value.assignee?.id ?? null)) {
        return;
    }

    router.patch(
        assign.url(detail.value.id),
        { assignee_id: value },
        { preserveScroll: true, preserveState: true, onFinish: leadChanged },
    );
}

// --- notes ---------------------------------------------------------------------------------------
const note = ref('');
const noteSaving = ref(false);
const noteError = ref<string | undefined>(undefined);
const noteKey = ref(0);

const notes = computed(
    () => detail.value?.timeline.filter((entry) => entry.kind === 'note') ?? [],
);

const noteIsEmpty = computed(
    () => note.value.replace(/<[^>]*>/g, '').trim() === '',
);

function addNote(): void {
    if (!detail.value || noteIsEmpty.value) {
        return;
    }

    noteSaving.value = true;
    noteError.value = undefined;

    router.post(
        storeNote.url(detail.value.id),
        { note: note.value },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                note.value = '';
                noteKey.value += 1;
            },
            onError: (errors) => {
                noteError.value = errors.note;
            },
            onFinish: () => {
                noteSaving.value = false;
                leadChanged();
            },
        },
    );
}

// --- follow-up -------------------------------------------------------------------------------------
const followUpValue = ref('');
const followUpSaving = ref(false);
const followUpError = ref<string | undefined>(undefined);

const earliest = computed(() =>
    open.value ? `${dhakaInputValue(new Date()).slice(0, 10)}T00:00` : '',
);
const latest = computed(() =>
    open.value
        ? dhakaInputValue(new Date(Date.now() + 2 * 365 * 86_400_000))
        : '',
);

watch(detail, (lead) => {
    followUpValue.value = lead?.follow_up_at
        ? dhakaInputValue(lead.follow_up_at)
        : '';
    followUpError.value = undefined;
});

function saveFollowUp(value: string): void {
    if (!detail.value) {
        return;
    }

    followUpSaving.value = true;
    followUpError.value = undefined;

    router.patch(
        followUp.url(detail.value.id),
        { follow_up_at: value === '' ? null : value },
        {
            preserveScroll: true,
            preserveState: true,
            onError: (errors) => {
                followUpError.value = errors.follow_up_at;
            },
            onFinish: () => {
                followUpSaving.value = false;
                leadChanged();
            },
        },
    );
}

// --- delete (Admin, DEL-02) --------------------------------------------------------------------------
const deleteOpen = ref(false);
const deleting = ref(false);

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

const sizeLabel = (bytes: number): string =>
    bytes >= 1_048_576
        ? `${(bytes / 1_048_576).toFixed(1)} MB`
        : `${Math.max(1, Math.round(bytes / 1024))} KB`;
</script>

<template>
    <Sheet v-model:open="open">
        <SheetContent class="w-full gap-0 overflow-hidden p-0 sm:max-w-[560px]">
            <SheetHeader class="border-b p-5 pr-12">
                <SheetTitle class="font-display text-xl font-medium">
                    {{ detail?.name ?? 'Lead' }}
                </SheetTitle>
                <SheetDescription>
                    <template v-if="detail">
                        {{ detail.type_label }}
                        <template v-if="detail.project">
                            · {{ detail.project.name }}</template
                        >
                        · received
                        <time
                            v-if="detail.created_at"
                            :datetime="detail.created_at"
                            >{{ formatDateTime(detail.created_at) }}</time
                        >
                    </template>
                    <template v-else>Lead details</template>
                </SheetDescription>
            </SheetHeader>

            <div
                v-if="loading"
                class="grid gap-3 p-5"
                role="status"
                aria-label="Loading the lead"
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
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="lead-drawer-status">Status</Label>
                            <AdminSelect
                                id="lead-drawer-status"
                                :model-value="detail.status"
                                :options="statuses"
                                :disabled="!detail.can.update"
                                @update:model-value="
                                    (value) =>
                                        value &&
                                        value !== detail?.status &&
                                        detail &&
                                        emit(
                                            'move',
                                            detail,
                                            value as LeadStatusValue,
                                        )
                                "
                            />
                            <p
                                v-if="!detail.can.update"
                                class="text-xs text-muted-foreground"
                            >
                                You can view this lead but not change it.
                            </p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="lead-drawer-owner">Owner</Label>
                            <AdminSelect
                                v-if="canAssign && detail.can.assign"
                                id="lead-drawer-owner"
                                :model-value="detail.assignee?.id ?? null"
                                :options="owners"
                                none-label="Unassigned"
                                placeholder="Unassigned"
                                @update:model-value="
                                    (value) =>
                                        reassign(
                                            value === null || value === ''
                                                ? null
                                                : Number(value),
                                        )
                                "
                            />
                            <p
                                v-else
                                id="lead-drawer-owner"
                                class="flex h-9 items-center text-sm"
                            >
                                {{ detail.assignee?.name ?? 'Unassigned' }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="detail.status === 'won' && detail.won_note"
                        class="rounded-md bg-success-50 px-3 py-2 text-sm text-success-700"
                    >
                        Won: {{ detail.won_note }}
                    </div>
                    <div
                        v-if="detail.status === 'lost' && detail.lost_reason"
                        class="rounded-md bg-error-50 px-3 py-2 text-sm text-error-700"
                    >
                        Lost: {{ detail.lost_reason }}
                    </div>

                    <div
                        v-if="detail.contact_visible"
                        class="flex flex-wrap gap-2"
                    >
                        <Button as-child variant="outline" size="sm">
                            <a :href="detail.links.call ?? undefined">
                                <Phone aria-hidden="true" />
                                Call {{ detail.phone }}
                            </a>
                        </Button>
                        <Button as-child variant="outline" size="sm">
                            <a
                                :href="detail.links.whatsapp ?? undefined"
                                target="_blank"
                                rel="noopener"
                            >
                                <MessageCircle aria-hidden="true" />
                                WhatsApp
                            </a>
                        </Button>
                        <Button
                            v-if="detail.links.email"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <a :href="detail.links.email">
                                <Mail aria-hidden="true" />
                                Email
                            </a>
                        </Button>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                        Phone numbers and emails are hidden for your role:
                        {{ detail.phone }}
                    </p>
                </div>

                <Tabs v-model="tab" class="flex min-h-0 flex-1 flex-col">
                    <TabsList class="mx-5 mt-4 grid grid-cols-4">
                        <TabsTrigger value="details">Details</TabsTrigger>
                        <TabsTrigger value="timeline">Timeline</TabsTrigger>
                        <TabsTrigger value="notes">
                            Notes<span
                                v-if="notes.length"
                                class="ml-1 text-xs tabular-nums"
                                >{{ notes.length }}</span
                            >
                        </TabsTrigger>
                        <TabsTrigger value="follow-up">Follow-up</TabsTrigger>
                    </TabsList>

                    <TabsContent value="details" class="grid gap-5 p-5">
                        <dl
                            class="grid grid-cols-[8.5rem_minmax(0,1fr)] gap-x-4 gap-y-3 text-sm"
                        >
                            <dt class="text-muted-foreground">Phone</dt>
                            <dd class="tabular-nums">{{ detail.phone }}</dd>
                            <template v-if="detail.whatsapp">
                                <dt class="text-muted-foreground">WhatsApp</dt>
                                <dd class="tabular-nums">
                                    {{ detail.whatsapp }}
                                </dd>
                            </template>
                            <template v-if="detail.email">
                                <dt class="text-muted-foreground">Email</dt>
                                <dd class="break-all">{{ detail.email }}</dd>
                            </template>
                            <template v-if="detail.country">
                                <dt class="text-muted-foreground">Country</dt>
                                <dd>{{ detail.country }}</dd>
                            </template>
                            <template v-if="detail.project">
                                <dt class="text-muted-foreground">Project</dt>
                                <dd>
                                    {{ detail.project.name
                                    }}<template v-if="detail.unit_type">
                                        · {{ detail.unit_type }}</template
                                    >
                                </dd>
                            </template>
                            <template
                                v-for="answer in detail.answers"
                                :key="answer.label"
                            >
                                <dt class="text-muted-foreground">
                                    {{ answer.label }}
                                </dt>
                                <dd>{{ answer.value }}</dd>
                            </template>
                            <dt class="text-muted-foreground">Asked</dt>
                            <dd>
                                {{ detail.touches_count + 1 }}
                                {{
                                    detail.touches_count === 0
                                        ? 'time'
                                        : 'times'
                                }}
                                <template v-if="detail.last_touched_at">
                                    · last
                                    {{ timeAgo(detail.last_touched_at) }}
                                </template>
                            </dd>
                        </dl>

                        <section v-if="detail.message" class="grid gap-1.5">
                            <h3 class="text-sm font-medium">Message</h3>
                            <p
                                class="rounded-md bg-muted/50 px-3 py-2 text-sm break-words whitespace-pre-line"
                            >
                                {{ detail.message }}
                            </p>
                        </section>

                        <section
                            v-if="detail.attachments.length"
                            class="grid gap-1.5"
                        >
                            <h3 class="text-sm font-medium">Documents</h3>
                            <ul class="grid gap-1">
                                <li
                                    v-for="file in detail.attachments"
                                    :key="file.url"
                                >
                                    <a
                                        :href="file.url"
                                        class="inline-flex items-center gap-2 text-sm text-primary underline-offset-4 hover:underline"
                                        download
                                    >
                                        <Download
                                            class="size-4"
                                            aria-hidden="true"
                                        />
                                        {{ file.name }}
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >{{ sizeLabel(file.size) }}</span
                                        >
                                    </a>
                                </li>
                            </ul>
                        </section>

                        <section class="grid gap-1.5">
                            <h3 class="text-sm font-medium">
                                Where it came from
                            </h3>
                            <dl
                                class="grid grid-cols-[8.5rem_minmax(0,1fr)] gap-x-4 gap-y-2 text-sm"
                            >
                                <dt class="text-muted-foreground">Source</dt>
                                <dd>{{ detail.source }}</dd>
                                <template
                                    v-for="(value, label) in detail.utm"
                                    :key="label"
                                >
                                    <dt class="text-muted-foreground">
                                        {{ label }}
                                    </dt>
                                    <dd class="break-words">{{ value }}</dd>
                                </template>
                                <template v-if="detail.referrer_host">
                                    <dt class="text-muted-foreground">
                                        Referrer
                                    </dt>
                                    <dd class="break-all">
                                        {{ detail.referrer_host }}
                                    </dd>
                                </template>
                                <template v-if="detail.form_location">
                                    <dt class="text-muted-foreground">Form</dt>
                                    <dd>{{ detail.form_location }}</dd>
                                </template>
                                <dt class="text-muted-foreground">Page</dt>
                                <dd class="min-w-0">
                                    <a
                                        v-if="
                                            detail.source_url.startsWith('/') &&
                                            !detail.source_url.startsWith(
                                                '/admin',
                                            )
                                        "
                                        :href="detail.source_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex max-w-full items-center gap-1 break-all text-primary underline-offset-4 hover:underline"
                                    >
                                        {{ detail.source_url }}
                                        <ExternalLink
                                            class="size-3.5 shrink-0"
                                            aria-hidden="true"
                                        />
                                    </a>
                                    <span v-else class="break-all">{{
                                        detail.source_url
                                    }}</span>
                                </dd>
                            </dl>
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
                    </TabsContent>

                    <TabsContent value="timeline" class="p-5">
                        <LeadTimeline :entries="detail.timeline" />
                    </TabsContent>

                    <TabsContent value="notes" class="grid gap-5 p-5">
                        <form
                            v-if="detail.can.update"
                            class="grid gap-3"
                            @submit.prevent="addNote"
                        >
                            <FormField
                                id="lead-note"
                                label="Add a note"
                                helper="Internal: only your team sees notes, never the visitor."
                                :error="noteError"
                            >
                                <template
                                    #default="{ id, describedby, invalid }"
                                >
                                    <RichTextField
                                        :id="id"
                                        :key="noteKey"
                                        v-model="note"
                                        :describedby="describedby"
                                        :invalid="invalid"
                                        :rows="4"
                                    />
                                </template>
                            </FormField>
                            <div>
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="noteSaving || noteIsEmpty"
                                >
                                    <Spinner v-if="noteSaving" />
                                    Add note
                                </Button>
                            </div>
                        </form>
                        <p v-else class="text-sm text-muted-foreground">
                            You can read notes but not add them.
                        </p>

                        <LeadTimeline :entries="notes" />
                    </TabsContent>

                    <TabsContent value="follow-up" class="grid gap-4 p-5">
                        <div
                            v-if="detail.follow_up_at"
                            class="flex items-start gap-3 rounded-md border px-3 py-2.5 text-sm"
                            :class="
                                detail.follow_up_overdue
                                    ? 'border-error-200 bg-error-50 text-error-700'
                                    : ''
                            "
                        >
                            <CalendarClock
                                class="mt-0.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            <p>
                                <span class="font-medium">
                                    {{
                                        detail.follow_up_overdue
                                            ? 'Overdue since'
                                            : 'Due'
                                    }}
                                    {{ formatDateTime(detail.follow_up_at) }}
                                </span>
                                <span class="block text-xs opacity-80">
                                    {{ timeAgo(detail.follow_up_at) }}
                                </span>
                            </p>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">
                            No follow-up is set for this lead.
                        </p>

                        <form
                            v-if="detail.can.update"
                            class="grid gap-3"
                            @submit.prevent="saveFollowUp(followUpValue)"
                        >
                            <FormField
                                id="lead-follow-up"
                                label="Follow up on"
                                helper="Dhaka time (UTC+6). It shows in “Overdue follow-ups” once the time has passed."
                                :error="followUpError"
                            >
                                <template
                                    #default="{ id, describedby, invalid }"
                                >
                                    <DateTimePicker
                                        :id="id"
                                        v-model="followUpValue"
                                        :min="earliest"
                                        :max="latest"
                                        placeholder="Choose a day"
                                        :invalid="invalid"
                                        :describedby="describedby"
                                    />
                                </template>
                            </FormField>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="
                                        followUpSaving || followUpValue === ''
                                    "
                                >
                                    <Spinner v-if="followUpSaving" />
                                    Save follow-up
                                </Button>
                                <Button
                                    v-if="detail.follow_up_at"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="followUpSaving"
                                    @click="saveFollowUp('')"
                                >
                                    Done — clear it
                                </Button>
                            </div>
                        </form>
                    </TabsContent>
                </Tabs>

                <div
                    v-if="detail.can.delete"
                    class="mt-auto flex items-center justify-between gap-3 border-t p-5"
                >
                    <p class="text-xs text-muted-foreground">
                        Deleting moves the lead to the Recycle bin for 365 days.
                    </p>
                    <Button
                        variant="outline"
                        size="sm"
                        class="text-destructive"
                        @click="deleteOpen = true"
                    >
                        <Trash2 aria-hidden="true" />
                        Delete lead
                    </Button>
                </div>
            </div>
        </SheetContent>
    </Sheet>

    <ConfirmDialog
        v-model:open="deleteOpen"
        :title="`Delete ${detail?.name ?? 'this lead'}?`"
        description="The lead and its timeline move to the Recycle bin. You can restore them for 365 days."
        confirm-label="Delete lead"
        destructive
        :processing="deleting"
        @confirm="confirmDelete"
    />
</template>
