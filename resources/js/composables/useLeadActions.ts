import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { needsReason } from '@/lib/leads';
import { status as statusRoute } from '@/routes/admin/leads';
import type { LeadStatusValue } from '@/types';

/** Raised after every change to a lead, so an open drawer reloads what it shows. */
export const leadsVersion = ref(0);

export function leadChanged(): void {
    leadsVersion.value += 1;
}

export type ReasonRequest = { id: number; name: string; to: 'won' | 'lost' };

/**
 * FR-LEAD-006: one way to move a lead along the pipeline, used by the board, the row menu and the
 * drawer. Won and Lost first ask for a reason (the dialog is the page's `LeadReasonDialog`).
 */
export function useLeadStatusChange() {
    const reasonRequest = ref<ReasonRequest | null>(null);
    const reasonOpen = ref(false);

    /** `onFailed` runs when the server refused the move (a card dragged here snaps back). */
    function move(
        lead: { id: number; name: string },
        to: LeadStatusValue,
        onFailed?: () => void,
    ): void {
        if (needsReason(to)) {
            reasonRequest.value = { id: lead.id, name: lead.name, to };
            reasonOpen.value = true;

            return;
        }

        let saved = false;

        router.patch(
            statusRoute.url(lead.id),
            { status: to },
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    saved = true;
                },
                onFinish: () => {
                    leadChanged();

                    if (!saved) {
                        onFailed?.();
                    }
                },
            },
        );
    }

    return { reasonRequest, reasonOpen, move };
}
