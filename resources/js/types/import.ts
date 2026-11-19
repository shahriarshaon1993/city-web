// ADM-IO-001 import wizard, queued exports (ADM-TBL-012) and personal data (NFR-PRIV-03).
// Shapes of App\Support\Import\ImportRunPresenter, ImportController and PrivacyController.

export type ImportField = {
    key: string;
    label: string;
    required: boolean;
    hint: string | null;
};

export type ImportTypeOption = {
    value: string;
    label: string;
    help: string;
    fields: ImportField[];
    template_url: string;
};

export type ImportRunStatus =
    | 'uploaded'
    | 'queued'
    | 'running'
    | 'finished'
    | 'failed';

export type ImportRun = {
    id: number;
    type: string;
    type_label: string;
    file: string;
    status: ImportRunStatus;
    status_label: string;
    working: boolean;
    total_rows: number;
    processed_rows: number;
    created: number;
    updated: number;
    skipped: number;
    failed: number;
    message: string | null;
    has_errors: boolean;
    created_at: string | null;
    finished_at: string | null;
    url: string;
    list_url: string;
    errors_url: string | null;
};

export type ExportRun = {
    id: number;
    filename: string;
    format: string;
    status: 'queued' | 'running' | 'ready' | 'expired' | 'failed';
    rows: number | null;
    message: string | null;
    created_at: string | null;
    expires_at: string | null;
    download_url: string | null;
};

export type ImportOption = { key: string; label: string; required: boolean };

export type ImportPreviewRow = {
    number: number;
    outcome: 'create' | 'update' | 'skip' | 'fail';
    label: string;
    message: string | null;
    values: Record<string, string>;
};

export type ImportPreview = {
    rows: ImportPreviewRow[];
    counts: { create: number; update: number; skip: number; fail: number };
};

export type PersonalDataLead = {
    id: number;
    name: string;
    phone: string;
    email: string | null;
    type: string;
    project: string | null;
    status: string;
    created_at: string | null;
    in_bin: boolean;
};

export type PersonalDataApplication = {
    id: number;
    name: string;
    phone: string;
    email: string;
    job: string;
    status: string;
    created_at: string | null;
    in_bin: boolean;
};

export type PersonalDataSubscription = {
    id: number;
    email: string;
    status: string;
    created_at: string | null;
    in_bin: boolean;
};

export type PersonalDataResults = {
    leads: PersonalDataLead[];
    applications: PersonalDataApplication[];
    subscriptions: PersonalDataSubscription[];
};
