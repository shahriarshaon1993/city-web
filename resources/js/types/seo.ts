/** One row of the redirects list (App\Http\Resources\Admin\RedirectRowResource). */
export type RedirectRow = {
    id: number;
    /** As typed: `/old-section/*` for a prefix rule. */
    source: string;
    target: string | null;
    code: 301 | 302 | 410;
    is_prefix: boolean;
    is_active: boolean;
    hits: number;
    last_hit_at: string | null;
    created_at: string | null;
    created_by: string | null;
    test_url: string | null;
    can: { edit: boolean; delete: boolean };
};

/** One row of the 404 monitor (NotFoundRowResource). */
export type NotFoundRow = {
    id: number;
    path: string;
    referrer: string | null;
    hits: number;
    first_seen_at: string;
    last_seen_at: string;
    is_ignored: boolean;
    /** The active rule that now answers this address, if any. */
    redirect: { target: string | null; code: number } | null;
    test_url: string;
    can: { manage: boolean };
};

/** Flash `import_report` after a CSV import (App\Actions\Redirects\ImportRedirects). */
export type RedirectImportReport = {
    created: number;
    updated: number;
    skipped: number;
    errors: Array<{ row: number; source: string; message: string }>;
};

export type IgnoreList = {
    patterns: string;
    defaults: string;
    is_custom: boolean;
};

/** What the dialog starts with: nothing (new), a row (edit) or an address from the 404 monitor. */
export type RedirectDraft = {
    id?: number;
    source: string;
    target: string;
    code: 301 | 302 | 410;
    is_active: boolean;
};
