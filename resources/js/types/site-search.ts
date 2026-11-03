// PG-19 contract (8.2): App\Http\Controllers\Site\SearchController with
// App\Support\Demo\DemoSiteSearch now, Scout in 17.2 (same shapes).

import type { Breadcrumb, NewsCard, ProjectCard } from '@/types/site';

export type PageResult = {
    title: string;
    description: string;
    url: string;
};

type Group<K extends string, T> = {
    key: K;
    heading: string;
    /** All matches; `items` holds at most 10 (FR-SRCH-010). */
    total: number;
    items: T[];
    see_all: { label: string; url: string } | null;
};

export type SearchGroup =
    | Group<'projects', ProjectCard>
    | Group<'news', NewsCard>
    | Group<'pages', PageResult>;

export type SearchSuggestion = {
    name: string;
    city: string;
    url: string;
    count: number;
};

export type SearchPageProps = {
    search: {
        query: string;
        /** 1 character: the minimum-length message instead of results. */
        too_short: boolean;
        groups: SearchGroup[];
        total: number;
        suggestions: SearchSuggestion[];
        breadcrumbs: Breadcrumb[];
    };
    copy: Record<string, string>;
};
