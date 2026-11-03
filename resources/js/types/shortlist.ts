// PG-20 / PG-21 contract (8.3): App\Http\Controllers\Site\ShortlistController
// with App\Support\Demo\DemoShortlist now, Project Resources in 13.3.

import type { Breadcrumb, ProjectCard } from '@/types/site';

export type ShortlistPageProps = {
    shortlist: {
        /** The URL carried `?ids=` (else the page reads storage first). */
        requested: boolean;
        /** The published IDs found, in the requested order (FR-SHORT-002). */
        ids: number[];
        projects: ProjectCard[];
        breadcrumbs: Breadcrumb[];
    };
    copy: Record<string, string>;
};

/** One table row; `null` renders "–", booleans render ✓ / –. */
export type CompareRow = {
    key: string;
    label: string;
    values: (string | boolean | null)[];
    /** The values are not all the same: the row is tinted. */
    differs: boolean;
};

export type CompareGroup = {
    key: 'facts' | 'amenities';
    /** Null: the group needs no heading row. */
    heading: string | null;
    rows: CompareRow[];
};

export type ComparePageProps = {
    compare: {
        requested: boolean;
        ids: number[];
        projects: ProjectCard[];
        groups: CompareGroup[];
        breadcrumbs: Breadcrumb[];
    };
    copy: Record<string, string>;
};
