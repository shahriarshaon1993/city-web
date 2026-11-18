// CMS-PUB (14.1): the publishing workflow state the editors receive from `Queries\Admin\PublishingState`
// and the JSON the revisions panel reads. Entity summaries (`ProjectSummary`, `AreaSummary`) extend it.
export type PublishingStatus = 'draft' | 'scheduled' | 'published' | 'archived';

export type PublishingType = 'project' | 'location' | 'page';

export type PendingRevision = {
    id: number;
    by: string | null;
    at: string | null;
    /** ISO time when the pending changes go live by themselves, or null. */
    publish_at: string | null;
    note: string | null;
};

export type WorkflowState = {
    type: PublishingType;
    id: number;
    status: PublishingStatus;
    status_label: string;
    /** CMS-PUB-006: sent back with every save; a stale value opens the conflict dialog. */
    lock_version: number;
    pending: PendingRevision | null;
    scheduled_at: string | null;
    published_at: string | null;
    public_url: string | null;
    /** Opens the signed preview in a new tab (redirects to the 60-minute link). */
    preview_url: string;
    can_overwrite: boolean;
};

/** `flash.conflict`, or the 409 body of an autosave (CMS-PUB-006). */
export type ConflictDetails = {
    by: string | null;
    at: string | null;
    lock_version: number;
};

export type RevisionRow = {
    id: number;
    kind: 'pending' | 'published';
    pending: boolean;
    note: string | null;
    author: string | null;
    created_at: string | null;
    publish_at: string | null;
};

export type RevisionChange = {
    key: string;
    label: string;
    kind: 'text' | 'html' | 'list';
    before: string;
    after: string;
    /** Server-escaped HTML with `<ins>` / `<del>` (rich text only). */
    markup: string | null;
    removed: string[];
    added: string[];
};
