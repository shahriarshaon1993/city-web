/** Admin leads (App\Http\Resources\Admin\Lead*Resource, ADM-SCR-11 / ADM-SCR-12). */

export type LeadStatusValue =
    | 'new'
    | 'needs_review'
    | 'contacted'
    | 'site_visit_scheduled'
    | 'site_visit_done'
    | 'negotiation'
    | 'won'
    | 'lost'
    | 'spam';

export type LeadPerson = { id: number; name: string };

export type LeadRow = {
    id: number;
    reference: string;
    name: string;
    type: string;
    type_label: string;
    /** Masked on the server for a user without `leads.view-contact`. */
    phone: string;
    whatsapp: string | null;
    email: string | null;
    contact_visible: boolean;
    project: LeadPerson | null;
    status: LeadStatusValue;
    status_label: string;
    assignee: LeadPerson | null;
    follow_up_at: string | null;
    follow_up_overdue: boolean;
    created_at: string | null;
    last_touched_at: string | null;
    touches_count: number;
    source: string;
    can: { update: boolean; assign: boolean; delete: boolean };
};

export type LeadCard = {
    id: number;
    name: string;
    type_label: string;
    phone: string;
    project: string | null;
    status: LeadStatusValue;
    assignee: LeadPerson | null;
    follow_up_at: string | null;
    follow_up_overdue: boolean;
    created_at: string | null;
    can: { update: boolean };
};

export type LeadBoardColumn = {
    status: LeadStatusValue;
    label: string;
    count: number;
    cards: LeadCard[];
};

export type LeadTimelineEntry = {
    id: number;
    kind:
        | 'submission'
        | 'touch'
        | 'status'
        | 'assignment'
        | 'note'
        | 'follow_up'
        | 'email';
    title: string;
    detail: string | null;
    /** Sanitised HTML, notes only. */
    html: string | null;
    by: string | null;
    at: string | null;
};

export type LeadDetail = LeadRow & {
    country: string | null;
    unit_type: string | null;
    message: string | null;
    answers: Array<{ label: string; value: string }>;
    form_location: string | null;
    source_url: string;
    referrer_host: string | null;
    utm: Record<string, string>;
    consent_text: string;
    lost_reason: string | null;
    won_note: string | null;
    attachments: Array<{ name: string; size: number; url: string }>;
    links: {
        call: string | null;
        whatsapp: string | null;
        email: string | null;
    };
    timeline: LeadTimelineEntry[];
};

export type LeadOption = { value: string; label: string };

export type LeadStatusOption = { value: LeadStatusValue; label: string };

export type LeadReasonLists = { won: LeadOption[]; lost: LeadOption[] };

export type NewLeadOptions = {
    channels: LeadOption[];
    countries: Array<{ code: string; name: string; dial: string }>;
    defaultCountry: string;
    projects: Array<{
        id: number;
        name: string;
        unit_types: Array<{ id: number; name: string }>;
    }>;
    assignees: LeadPerson[];
};

/** Props both leads screens (table and board) share, from LeadController::shared(). */
export type LeadsShared = {
    canCreate: boolean;
    canAssign: boolean;
    canExport: boolean;
    canShareViews: boolean;
    binCount: number;
    assignees: LeadPerson[];
    reasons: LeadReasonLists;
    statuses: LeadStatusOption[];
    openLead: LeadDetail | null;
};

export type SubscriberRow = {
    id: number;
    email: string;
    status: 'pending' | 'active' | 'unsubscribed';
    status_label: string;
    source: string | null;
    confirmed_at: string | null;
    created_at: string | null;
    can: { unsubscribe: boolean; delete: boolean; erase: boolean };
};
