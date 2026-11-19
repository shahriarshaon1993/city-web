// ADM-SCR-10 Careers admin (17.1): the job post editor and list, the applications list and drawer. Shapes of
// App\Queries\Admin\JobPostEditor, JobPostRowResource, JobApplicationRowResource and
// JobApplicationDetailResource.
import type { AssetMap, SeoForm } from '@/types';

export type JobStatusValue = 'draft' | 'open' | 'closed';

export type JobPostFormData = {
    title: string;
    slug: string;
    department: string;
    office_id: number | null;
    employment_type: string;
    experience: string;
    salary_text: string;
    salary_min_bdt: number | null;
    salary_max_bdt: number | null;
    summary: string;
    description: string;
    responsibilities: string;
    requirements: string;
    benefits: string;
    /** ISO date, Asia/Dhaka. */
    deadline: string;
    status: JobStatusValue;
    seo: SeoForm;
};

export type JobPostOptions = {
    offices: { value: number; label: string }[];
    employment_types: { value: string; label: string }[];
    statuses: { value: JobStatusValue; label: string }[];
    departments: string[];
    /** Today in Dhaka, ISO. */
    today: string;
    limits: {
        title: number;
        summary: number;
        department: number;
        experience: number;
        salary_text: number;
    };
};

export type JobPostSummary = {
    id: number;
    title: string;
    path: string;
    status: JobStatusValue;
    deadline_passed: boolean;
    applications_count: number;
    closed_at: string | null;
};

export type JobPostEditProps = {
    job: JobPostSummary | null;
    form: JobPostFormData;
    assets: AssetMap;
    options: JobPostOptions;
    hasOffices: boolean;
    lastSaved: string | null;
    can: {
        delete: boolean;
        duplicate: boolean;
        update: boolean;
        applications: boolean;
    };
};

export type JobPostRow = {
    id: number;
    title: string;
    path: string;
    department: string;
    office: string | null;
    employment_type: { value: string; label: string };
    status: { value: JobStatusValue; label: string };
    /** ISO date. */
    deadline: string;
    /** Open, but the 00:05 scheduler has not closed it yet. */
    deadline_passed: boolean;
    closed_at: string | null;
    applications_count: number;
    new_applications_count: number;
    updated_at: string | null;
    edit_url: string;
    public_url: string | null;
    can: {
        edit: boolean;
        delete: boolean;
        duplicate: boolean;
        applications: boolean;
    };
};

export type ApplicationStatusValue =
    | 'new'
    | 'shortlisted'
    | 'interview'
    | 'rejected'
    | 'hired';

export type ApplicationStatusOption = {
    value: ApplicationStatusValue;
    label: string;
};

export type JobApplicationRow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    job: { id: number; title: string } | null;
    current_position: string | null;
    years_experience: number | null;
    status: ApplicationStatusOption;
    needs_review: boolean;
    review_label: string | null;
    created_at: string | null;
    can: { update: boolean; delete: boolean };
};

export type JobApplicationDetail = {
    id: number;
    reference: string;
    name: string;
    email: string;
    phone: string;
    links: { call: string; whatsapp: string | null; email: string };
    job: { id: number; title: string; edit_url: string | null } | null;
    current_position: string | null;
    years_experience: number | null;
    expected_salary: string | null;
    linkedin_url: string | null;
    cover_letter: string | null;
    cv: { name: string; size: number; url: string };
    status: ApplicationStatusValue;
    statuses: ApplicationStatusOption[];
    notes: string | null;
    needs_review: boolean;
    review_label: string | null;
    consent_text: string;
    created_at: string | null;
    updated_at: string | null;
    /** When the application and its CV are removed if nobody touches it (NFR-PRIV-04). */
    removal_at: string | null;
    can: { update: boolean; delete: boolean };
};
