<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import StatusBadge from '@/components/admin/StatusBadge.vue';
import { formatDateTime } from '@/lib/admin-format';
import { index as leadsIndex } from '@/routes/admin/leads';
import type { WebhookDeliveryRow } from '@/types';

// FR-LEAD-012 / DM-WEBH: the last 20 deliveries of the lead webhook, newest first. Kept 30 days.
defineProps<{ deliveries: WebhookDeliveryRow[] }>();

const STATE: Record<
    WebhookDeliveryRow['state'],
    { label: string; tone: string }
> = {
    delivered: { label: 'Delivered', tone: 'success' },
    queued: { label: 'Waiting', tone: 'info' },
    retrying: { label: 'Will retry', tone: 'warning' },
    failed: { label: 'Failed', tone: 'error' },
    skipped: { label: 'Not sent', tone: 'neutral' },
};
</script>

<template>
    <section
        aria-labelledby="webhook-deliveries"
        class="mx-auto mt-10 max-w-5xl border-t pt-8"
    >
        <h2 id="webhook-deliveries" class="font-display text-lg font-medium">
            Recent deliveries
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">
            The last 20 calls to your webhook. A failed call is tried again
            after 1, 5 and 15 minutes; entries are removed after 30 days.
        </p>

        <div
            v-if="deliveries.length === 0"
            class="mt-4 rounded-md border border-dashed px-4 py-8 text-center text-sm text-muted-foreground"
        >
            Nothing sent yet. Turn the webhook on, save, and the next lead
            appears here.
        </div>

        <div v-else class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-xs text-muted-foreground">
                    <tr class="border-b">
                        <th scope="col" class="py-2 pr-4 font-medium">When</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Event</th>
                        <th scope="col" class="py-2 pr-4 font-medium">Lead</th>
                        <th scope="col" class="py-2 pr-4 font-medium">
                            Result
                        </th>
                        <th scope="col" class="py-2 pr-4 font-medium">Tries</th>
                        <th scope="col" class="py-2 font-medium">Answer</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="row in deliveries" :key="row.id">
                        <td class="py-2.5 pr-4 whitespace-nowrap tabular-nums">
                            {{
                                row.created_at
                                    ? formatDateTime(row.created_at)
                                    : ''
                            }}
                        </td>
                        <td class="py-2.5 pr-4 font-mono text-xs">
                            {{ row.event }}
                        </td>
                        <td class="py-2.5 pr-4">
                            <Link
                                v-if="row.lead"
                                :href="
                                    leadsIndex.url({
                                        query: { lead: row.lead.id },
                                    })
                                "
                                class="underline-offset-4 hover:underline"
                            >
                                {{ row.lead.name }}
                            </Link>
                            <span v-else class="text-muted-foreground"
                                >Erased</span
                            >
                        </td>
                        <td class="py-2.5 pr-4">
                            <StatusBadge
                                :label="STATE[row.state].label"
                                :tone="STATE[row.state].tone"
                            />
                            <span
                                v-if="row.status_code"
                                class="ml-2 font-mono text-xs text-muted-foreground"
                                >{{ row.status_code }}</span
                            >
                        </td>
                        <td class="py-2.5 pr-4 tabular-nums">
                            {{ row.attempts }}
                        </td>
                        <td
                            class="max-w-[22rem] truncate py-2.5 text-muted-foreground"
                            :title="row.response_excerpt ?? undefined"
                        >
                            {{ row.response_excerpt ?? '—' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
