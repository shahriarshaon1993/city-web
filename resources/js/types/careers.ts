// Careers contract (8.2): App\Http\Controllers\Site\CareerController with
// App\Support\Demo\DemoCareers now, the JobPost resources in 17.1.

import type { PageBlock } from '@/types/blocks';
import type { Breadcrumb } from '@/types/site';

export type EmploymentType =
    | 'full_time'
    | 'part_time'
    | 'contract'
    | 'internship';

/** One PG-15 role row (JobRowResource). */
export type JobRow = {
    id: number;
    slug: string;
    url: string;
    title: string;
    department: string;
    location: string;
    employment_type: EmploymentType;
    employment_type_label: string;
    /** ISO date. */
    deadline: string;
    /** "Closes in 6 days"; null once closed. Worked out on the server. */
    closes_label: string | null;
};

/** PG-16 (JobPostResource). The salary is null unless set (FR-CAR-001). */
export type JobPost = JobRow & {
    summary: string;
    experience: string | null;
    salary_text: string | null;
    is_open: boolean;
    /** ISO date; null while open. */
    closed_at: string | null;
    /** Sanitised rich text per section; empty sections are left out. */
    sections: { id: string; heading: string; body: string }[];
    breadcrumbs: Breadcrumb[];
};

/** A filter option; `url` is a real link (owner 8.2: query links). */
export type CareerFacetOption = {
    value: string;
    label: string;
    count: number;
    url: string;
    active: boolean;
};

export type CareerFacet = {
    key: 'department' | 'location' | 'type';
    label: string;
    all: Omit<CareerFacetOption, 'value'>;
    options: CareerFacetOption[];
};

export type CareersCopy = Record<string, string>;

export type CareersPageProps = {
    blocks: PageBlock[];
    careers: {
        roles: JobRow[];
        /** Open roles before filtering; 0 shows the CV drop (FR-CAR-004). */
        total_open: number;
        filtered: boolean;
        facets: CareerFacet[];
    };
    copy: CareersCopy;
};

export type CareerJobPageProps = {
    job: JobPost;
    /** Shown on a closed role only. */
    other_roles: JobRow[];
    copy: CareersCopy;
};
