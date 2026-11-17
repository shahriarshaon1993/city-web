<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CalendarClock, Copy, ExternalLink, Eye, Undo2 } from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import ReasonButton from '@/components/admin/ReasonButton.vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import PublishChecklist from '@/components/admin/catalog/PublishChecklist.vue';
import ScheduleDialog from '@/components/admin/publishing/ScheduleDialog.vue';
import { Button } from '@/components/ui/button';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { sendJson } from '@/lib/http';
import { showToast } from '@/lib/notify';
import {
    discard,
    previewLink,
    publish,
    unpublish,
    unschedule,
} from '@/routes/admin/publishing';
import type { ChecklistItem, WorkflowState } from '@/types';

// CMS-PUB workflow block of the right rail, the same for every publishable entity: status (with the
// pending changes and the schedule spelled out, HELP-06), the verbs that fit the state — Publish /
// Schedule for a draft, Publish now / Unschedule for a scheduled one, Publish changes / Schedule
// changes / Discard changes for a published one with edits waiting, Unpublish — Preview and a
// shareable preview link, and the publish checklist of the working copy (AC-PROJ-03). Disabled
// verbs say why (HELP-08).
const props = defineProps<{
    workflow: WorkflowState | null;
    noun: string;
    checklist: ChecklistItem[];
    dirty: boolean;
    canPublish: boolean;
    canEdit: boolean;
    /** Public path of the saved entity, shown under the status once it is live. */
    path: string;
}>();

const emit = defineEmits<{ fix: [item: ChecklistItem] }>();

const uid = useId();
const confirm = useConfirm();
const scheduling = ref(false);

const status = computed(() => props.workflow?.status ?? 'draft');
const pending = computed(() => props.workflow?.pending ?? null);
const live = computed(() => status.value === 'published');
const scheduled = computed(() => status.value === 'scheduled');
const open = computed(
    () => props.checklist.filter((item) => !item.passed).length,
);

const tone = computed(() => {
    if (pending.value && live.value) {
        return 'warning';
    }

    const tones: Record<string, string> = {
        published: 'success',
        scheduled: 'info',
        archived: 'warning',
    };

    return tones[status.value] ?? 'neutral';
});

const label = computed(() =>
    pending.value && live.value
        ? 'Pending changes'
        : (props.workflow?.status_label ?? 'Not saved yet'),
);

const statusLine = computed(() => {
    const state = props.workflow;

    if (!state) {
        return null;
    }

    if (scheduled.value && state.scheduled_at) {
        return `Goes live ${formatDateTime(state.scheduled_at)} (Dhaka time).`;
    }

    if (live.value && pending.value) {
        const when = pending.value.at ? timeAgo(pending.value.at) : '';
        const by = pending.value.by ? ` by ${pending.value.by}` : '';
        const goes = pending.value.publish_at
            ? ` They go live ${formatDateTime(pending.value.publish_at)}.`
            : '';

        return `The live version is older. Changes saved${by} ${when}.${goes}`.replace(
            /\s+\./g,
            '.',
        );
    }

    if (live.value && state.published_at) {
        return `Live since ${formatDateTime(state.published_at).split(' · ')[0]}.`;
    }

    return 'Hidden from the website until you publish it.';
});

const unsaved = 'Save your changes first.';

const publishReason = computed(() => {
    if (!props.workflow) {
        return `Save the ${props.noun} first, then publish it.`;
    }

    if (props.dirty) {
        return unsaved;
    }

    return open.value > 0
        ? `${open.value} checklist ${open.value === 1 ? 'item is' : 'items are'} open.`
        : null;
});

const scheduleReason = computed(() => publishReason.value);
const dirtyReason = computed(() => (props.dirty ? unsaved : null));

const base = computed(() => ({
    type: props.workflow?.type ?? 'project',
    id: props.workflow?.id ?? 0,
}));

const schedule = computed(() =>
    live.value ? pending.value?.publish_at : props.workflow?.scheduled_at,
);

function post(url: string): void {
    router.post(url, {}, { preserveScroll: true });
}

async function discardChanges(): Promise<void> {
    const answer = await confirm({
        title: 'Discard these changes?',
        description: `The live ${props.noun} stays as it is. The changes saved since it was published are deleted and cannot be brought back.`,
        confirmLabel: 'Discard changes',
        destructive: true,
    });

    if (answer) {
        post(discard.url(base.value));
    }
}

async function copyPreviewLink(): Promise<void> {
    try {
        const result = await sendJson<{ url: string; minutes: number }>(
            'POST',
            previewLink.url(base.value),
        );

        await navigator.clipboard.writeText(result.url);
        showToast({
            type: 'success',
            title: 'Preview link copied',
            description: `Anyone with the link can see it for ${result.minutes} minutes, no account needed.`,
        });
    } catch {
        showToast({ type: 'error', title: 'Could not copy the link' });
    }
}

async function copyPublicUrl(): Promise<void> {
    if (!props.workflow?.public_url) {
        return;
    }

    try {
        await navigator.clipboard.writeText(props.workflow.public_url);
        showToast({ type: 'success', title: 'Link copied' });
    } catch {
        showToast({ type: 'error', title: 'Could not copy the link' });
    }
}
</script>

<template>
    <div class="grid grid-cols-[minmax(0,1fr)] gap-4 border-t pt-4">
        <section
            class="grid grid-cols-[minmax(0,1fr)] gap-3"
            :aria-labelledby="`${uid}-status`"
        >
            <div class="flex items-center justify-between gap-2">
                <h2 :id="`${uid}-status`" class="text-sm font-semibold">
                    Status
                </h2>
                <StatusBadge :label="label" :tone="tone" />
            </div>

            <p v-if="statusLine" class="text-xs text-muted-foreground">
                {{ statusLine }}
            </p>

            <div v-if="workflow?.public_url" class="flex items-center gap-1">
                <a
                    :href="workflow.public_url"
                    target="_blank"
                    rel="noopener"
                    class="min-w-0 flex-1 truncate rounded-md bg-muted px-2 py-1.5 font-mono text-xs hover:underline"
                    >{{ path }}</a
                >
                <Button
                    type="button"
                    variant="ghost"
                    size="icon-sm"
                    aria-label="Copy the public link"
                    @click="copyPublicUrl"
                >
                    <Copy aria-hidden="true" />
                </Button>
            </div>

            <template v-if="workflow && canPublish">
                <!-- Draft -->
                <template v-if="status === 'draft'">
                    <ReasonButton
                        type="button"
                        variant="outline"
                        class="w-full"
                        :reason="publishReason"
                        @click="post(publish.url(base))"
                    >
                        Publish
                    </ReasonButton>
                    <ReasonButton
                        type="button"
                        variant="outline"
                        class="w-full"
                        :reason="scheduleReason"
                        @click="scheduling = true"
                    >
                        <CalendarClock aria-hidden="true" />
                        Schedule…
                    </ReasonButton>
                </template>

                <!-- Scheduled -->
                <template v-else-if="scheduled">
                    <ReasonButton
                        type="button"
                        variant="outline"
                        class="w-full"
                        :reason="publishReason"
                        @click="post(publish.url(base))"
                    >
                        Publish now
                    </ReasonButton>
                    <ReasonButton
                        type="button"
                        variant="outline"
                        class="w-full"
                        :reason="dirtyReason"
                        @click="scheduling = true"
                    >
                        <CalendarClock aria-hidden="true" />
                        Change time…
                    </ReasonButton>
                    <ReasonButton
                        type="button"
                        variant="ghost"
                        class="w-full"
                        :reason="dirtyReason"
                        @click="post(unschedule.url(base))"
                    >
                        Unschedule
                    </ReasonButton>
                </template>

                <!-- Published -->
                <template v-else-if="live">
                    <template v-if="pending">
                        <ReasonButton
                            type="button"
                            variant="outline"
                            class="w-full"
                            :reason="publishReason"
                            @click="post(publish.url(base))"
                        >
                            Publish changes
                        </ReasonButton>
                        <ReasonButton
                            v-if="!pending.publish_at"
                            type="button"
                            variant="outline"
                            class="w-full"
                            :reason="scheduleReason"
                            @click="scheduling = true"
                        >
                            <CalendarClock aria-hidden="true" />
                            Schedule changes…
                        </ReasonButton>
                        <ReasonButton
                            v-else
                            type="button"
                            variant="ghost"
                            class="w-full"
                            :reason="dirtyReason"
                            @click="post(unschedule.url(base))"
                        >
                            Unschedule changes
                        </ReasonButton>
                        <ReasonButton
                            v-if="canEdit"
                            type="button"
                            variant="ghost"
                            class="w-full text-destructive hover:text-destructive"
                            :reason="dirtyReason"
                            @click="discardChanges"
                        >
                            <Undo2 aria-hidden="true" />
                            Discard changes
                        </ReasonButton>
                    </template>
                    <ReasonButton
                        type="button"
                        variant="ghost"
                        class="w-full"
                        :reason="dirtyReason"
                        @click="post(unpublish.url(base))"
                    >
                        Unpublish
                    </ReasonButton>
                </template>
            </template>

            <template v-if="workflow && canEdit && (!live || pending)">
                <ReasonButton
                    v-if="dirty"
                    type="button"
                    variant="ghost"
                    class="w-full"
                    reason="Save your changes first. The preview shows the saved version."
                >
                    <Eye aria-hidden="true" />
                    {{ pending ? 'Preview changes' : 'Preview' }}
                </ReasonButton>
                <Button v-else as-child variant="ghost" class="w-full">
                    <a
                        :href="workflow.preview_url"
                        target="_blank"
                        rel="noopener"
                    >
                        <Eye aria-hidden="true" />
                        {{ pending ? 'Preview changes' : 'Preview' }}
                    </a>
                </Button>
                <Button
                    type="button"
                    variant="ghost"
                    class="w-full"
                    @click="copyPreviewLink"
                >
                    <Copy aria-hidden="true" />
                    Copy preview link
                </Button>
            </template>

            <Button
                v-if="live && workflow?.public_url"
                as-child
                variant="ghost"
                class="w-full"
            >
                <a :href="workflow.public_url" target="_blank" rel="noopener">
                    <ExternalLink aria-hidden="true" />
                    View on site
                </a>
            </Button>
        </section>

        <PublishChecklist
            v-if="workflow && checklist.length > 0"
            :items="checklist"
            :dirty="dirty"
            class="border-t pt-4"
            @fix="(item) => emit('fix', item)"
        />

        <ScheduleDialog
            v-if="workflow"
            v-model:open="scheduling"
            :type="base.type"
            :id="base.id"
            :current="schedule ?? null"
            :changes="live"
            :noun="noun"
        />
    </div>
</template>
