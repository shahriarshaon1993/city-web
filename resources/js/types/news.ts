// PG-12…14 props (NewsCardResource, PostResource, FR-NEWS). Part A fills them
// from App\Support\Demo\DemoNews (DEC-29).

import type { Breadcrumb, NewsCard, ProjectCard, Seo } from '@/types/site';

export type NewsTag = { name: string; slug: string; url: string };

/** A category pill: `slug: null` is the "All" entry. */
export type NewsCategoryLink = {
    slug: string | null;
    name: string;
    url: string;
    count: number;
};

/** CMP-PAGI-01; `null` in `pages` = ellipsis. */
export type PageLinksData = {
    prev: string | null;
    next: string | null;
    pages: ({ page: number; url: string } | null)[];
};

/** Labels and headings of PG-12…14 (CMS-000a: copy arrives as props). */
export type NewsCopy = {
    index_title: string;
    index_intro: string;
    all_label: string;
    categories_label: string;
    empty_title: string;
    empty_body: string;
    empty_action: string;
    by_label: string;
    projects_heading: string;
    tags_label: string;
    related_heading: string;
    related_view_all: string;
    newsletter_heading: string;
    newsletter_text: string;
};

export type NewsArchive = {
    kind: 'index' | 'category' | 'tag';
    title: string;
    intro: string;
    breadcrumbs: Breadcrumb[];
    /** Category slug on a category archive. */
    active_category: string | null;
    categories: NewsCategoryLink[];
    /** PG-12 page 1 only. */
    featured: NewsCard | null;
    posts: NewsCard[];
    meta: {
        page: number;
        last_page: number;
        total: number;
        pagination: PageLinksData;
    };
};

export type NewsPageProps = {
    news: NewsArchive;
    copy: NewsCopy;
    seo: Seo;
};

/** PG-14 body (FR-NEWS-001): `body` is sanitised rich text with H2–H4 only. */
export type NewsArticle = NewsCard & {
    body: string;
    author: { name: string; role: string };
    tags: NewsTag[];
    /** 0–3 related projects. */
    projects: ProjectCard[];
};

export type ShareNetwork = 'facebook' | 'linkedin' | 'whatsapp';

/** CMP-SHARE-01: share-intent links; no SDKs. */
export type ShareData = {
    /** Absolute URL of the page. */
    url: string;
    title: string;
    heading: string;
    copy_label: string;
    copied_message: string;
    failed_message: string;
    links: { network: ShareNetwork; label: string; href: string }[];
};

export type NewsArticlePageProps = {
    article: NewsArticle;
    related: NewsCard[];
    share: ShareData;
    breadcrumbs: Breadcrumb[];
    copy: NewsCopy;
    seo: Seo;
};
