import type { LeadStatusValue } from '@/types';

/** Tone of the status badge (CMP-ADM-STATUS); the label always carries the meaning. */
export const STATUS_TONE: Record<LeadStatusValue, string> = {
    new: 'info',
    needs_review: 'warning',
    contacted: 'neutral',
    site_visit_scheduled: 'info',
    site_visit_done: 'info',
    negotiation: 'warning',
    won: 'success',
    lost: 'error',
    spam: 'neutral',
};

/** FR-LEAD-006: moving a lead to one of these asks for a reason first. */
export function needsReason(status: LeadStatusValue): status is 'won' | 'lost' {
    return status === 'won' || status === 'lost';
}
