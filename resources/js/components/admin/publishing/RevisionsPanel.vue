<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Eye, GitCompare, RotateCcw } from '@lucide/vue';
import { computed, onMounted, ref, watch } from 'vue';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { useConfirm } from '@/composables/useConfirm';
import { formatDateTime, timeAgo } from '@/lib/admin-format';
import { sendJson } from '@/lib/http';
import {
    diff,
    preview,
    restore,
    revisions as revisionsRoute,
} from '@/routes/admin/publishing';
import type { RevisionChange, RevisionRow } from '@/types';

// CMS-PUB-004 / CMP-ADM-REV: the pending changes and the last 30 publishes of one item. Compare shows
// what a version changes compared with the live one, field by field (rich text word by word);
// Restore turns a version into pending changes — nothing goes live until "Publish changes".
const props = defineProps<{
    type: string;
    id: number;
    /** Changes whenever the item is saved or published, so the list reloads. */
    refreshKey: string | null;
    canRestore: boolean;
    noun: string;
}>();

const confirm = useConfirm();
const rows = ref<RevisionRow[] | null>(null);
const failed = ref(false);
const base = computed(() => ({ type: props.type, id: props.id }));
const hasPending = computed(() => rows.value?.some((row) => row.pending));

async function load(): Promise<void> {
    failed.value = false;

    try {
        const answer = await sendJson<{ revisions: RevisionRow[] }>(
            'GET',
            revisionsRoute.url(base.value),
        );

        rows.value = answer.revisions;
    } catch {
        failed.value = true;
    }
}

onMounted(load);
watch(() => props.refreshKey, load);

// --- compare ------------------------------------------------------------------------------------
const comparing = ref<RevisionRow | null>(null);
const changes = ref<RevisionChange[] | null>(null);
const compareFailed = ref(false);
const compareOpen = computed({
    get: () => comparing.value !== null,
    set: (open: boolean) => {
        if (!open) {
            comparing.value = null;
            changes.value = null;
        }
    },
});

async function compare(row: RevisionRow): Promise<void> {
    comparing.value = row;
    changes.value = null;
    compareFailed.value = false;

    try {
        const answer = await sendJson<{ changes: RevisionChange[] }>(
            'GET',
            diff.url({ ...base.value, revision: row.id }),
        );

        changes.value = answer.changes;
    } catch {
        compareFailed.value = true;
    }
}

// --- restore ------------------------------------------------------------------------------------
async function restoreRevision(row: RevisionRow): Promise<void> {
    const answer = await confirm({
        title: 'Restore this version?',
        subject: row.created_at ? formatDateTime(row.created_at) : undefined,
        description: hasPending.value
            ? `It becomes the pending changes and replaces the ones waiting now. The live ${props.noun} stays as it is until you publish.`
            : `It becomes pending changes. The live ${props.noun} stays as it is until you publish.`,
        confirmLabel: 'Restore version',
    });

    if (answer) {
        router.post(
            restore.url({ ...base.value, revision: row.id }),
            {},
            { preserveScroll: true },
        );
    }
}

function title(row: RevisionRow): string {
    if (row.pending) {
        return 'Pending changes';
    }

    return row.note ?? 'Published';
}
</script>

<template>
    <div class="grid gap-4">
        <p class="text-sm text-muted-foreground">
            The changes waiting to go live, then every publish — the last 30 are
            kept. Restoring a version creates pending changes; nothing is public
            until you publish them.
        </p>

        <div
            v-if="rows === null && !failed"
            class="grid gap-2"
            aria-busy="true"
        >
            <Skeleton class="h-14 w-full" />
            <Skeleton class="h-14 w-full" />
        </div>

        <p v-else-if="failed" class="text-sm text-destructive" role="alert">
            Could not load the revisions.
            <Button
                type="button"
                variant="link"
                class="h-auto p-0"
                @click="load"
                >Try again</Button
            >
        </p>

        <p
            v-else-if="rows && rows.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            No revisions yet. A version is stored each time the {{ noun }} is
            published.
        </p>

        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="row in rows"
                :key="row.id"
                class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3"
            >
                <div class="min-w-0 flex-1">
                    <p
                        class="flex flex-wrap items-center gap-2 text-sm font-medium"
                    >
                        {{ title(row) }}
                        <StatusBadge
                            v-if="row.pending"
                            label="Not live"
                            tone="warning"
                        />
                        <StatusBadge
                            v-if="row.publish_at"
                            :label="`Goes live ${formatDateTime(row.publish_at)}`"
                            tone="info"
                        />
                    </p>
                    <p class="text-xs text-muted-foreground">
                        <template v-if="row.created_at">
                            <time
                                :datetime="row.created_at"
                                :title="formatDateTime(row.created_at)"
                                >{{ formatDateTime(row.created_at) }}</time
                            >
                            · {{ timeAgo(row.created_at) }}
                        </template>
                        <template v-if="row.author">
                            · {{ row.author }}</template
                        >
                    </p>
                </div>

                <div class="flex items-center gap-1">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="compare(row)"
                    >
                        <GitCompare aria-hidden="true" />
                        Compare
                    </Button>
                    <Button
                        v-if="canRestore"
                        as-child
                        variant="ghost"
                        size="sm"
                    >
                        <a
                            :href="
                                preview.url(base, {
                                    query: { revision: row.id },
                                })
                            "
                            target="_blank"
                            rel="noopener"
                        >
                            <Eye aria-hidden="true" />
                            Preview
                        </a>
                    </Button>
                    <Button
                        v-if="canRestore && !row.pending"
                        type="button"
                        variant="ghost"
                        size="sm"
                        @click="restoreRevision(row)"
                    >
                        <RotateCcw aria-hidden="true" />
                        Restore
                    </Button>
                </div>
            </li>
        </ul>

        <Dialog v-model:open="compareOpen">
            <DialogContent class="max-h-[88vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>{{
                        comparing ? title(comparing) : 'Compare'
                    }}</DialogTitle>
                    <DialogDescription>
                        What this version changes compared with the live
                        {{ noun }}.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="changes === null && !compareFailed" aria-busy="true">
                    <Skeleton class="h-24 w-full" />
                </div>
                <p
                    v-else-if="compareFailed"
                    class="text-sm text-destructive"
                    role="alert"
                >
                    Could not load the comparison.
                </p>
                <p
                    v-else-if="changes && changes.length === 0"
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    No differences: this version matches the live {{ noun }}.
                </p>

                <dl v-else class="grid gap-4">
                    <div
                        v-for="change in changes"
                        :key="change.key"
                        class="grid gap-2 border-b pb-4 last:border-b-0"
                    >
                        <dt class="text-sm font-medium">{{ change.label }}</dt>

                        <dd
                            v-if="change.kind === 'html'"
                            class="rounded-md bg-muted/40 p-3 text-sm leading-relaxed [&_del]:rounded-sm [&_del]:bg-error-50 [&_del]:text-error-700 [&_del]:line-through [&_ins]:rounded-sm [&_ins]:bg-success-50 [&_ins]:text-success-700 [&_ins]:no-underline"
                            v-html="change.markup"
                        />

                        <dd
                            v-else-if="change.kind === 'list'"
                            class="grid gap-1 text-sm"
                        >
                            <p
                                v-for="line in change.removed"
                                :key="`r-${line}`"
                                class="rounded-sm bg-error-50 px-2 py-1 text-error-700"
                            >
                                <span class="sr-only">Removed: </span>
                                <span aria-hidden="true">− </span>{{ line }}
                            </p>
                            <p
                                v-for="line in change.added"
                                :key="`a-${line}`"
                                class="rounded-sm bg-success-50 px-2 py-1 text-success-700"
                            >
                                <span class="sr-only">Added: </span>
                                <span aria-hidden="true">+ </span>{{ line }}
                            </p>
                        </dd>

                        <dd v-else class="grid gap-2 text-sm sm:grid-cols-2">
                            <p
                                class="rounded-sm bg-error-50 px-2 py-1 text-error-700"
                            >
                                <span class="block text-xs opacity-80"
                                    >Live</span
                                >
                                {{ change.before || '—' }}
                            </p>
                            <p
                                class="rounded-sm bg-success-50 px-2 py-1 text-success-700"
                            >
                                <span class="block text-xs opacity-80"
                                    >This version</span
                                >
                                {{ change.after || '—' }}
                            </p>
                        </dd>
                    </div>
                </dl>
            </DialogContent>
        </Dialog>
    </div>
</template>
