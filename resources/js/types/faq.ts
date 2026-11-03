// PG-18 contract (8.2): App\Http\Controllers\Site\FaqController with
// App\Support\Demo\DemoFaqs now, the FAQ resources in 16.4.

import type { CtaBandBlock } from '@/types/blocks';
import type { Breadcrumb } from '@/types/site';

/** FR-SOC-003 categories. */
export type FaqCategorySlug =
    | 'buying'
    | 'payment'
    | 'legal'
    | 'nrb'
    | 'landowner'
    | 'after_sales'
    | 'general';

export type FaqEntry = {
    id: number;
    question: string;
    /** Limited rich text: paragraph, bold, link, list. */
    answer: string;
    category: FaqCategorySlug;
};

export type FaqCategory = {
    slug: FaqCategorySlug;
    label: string;
    count: number;
};

export type FaqCopy = {
    title: string;
    intro: string;
    search_label: string;
    search_placeholder: string;
    topics_label: string;
    all_label: string;
    results_count: string;
    results_count_one: string;
    empty_title: string;
    empty_body: string;
    empty_all_action: string;
    empty_clear_action: string;
    breadcrumbs: Breadcrumb[];
};

export type FaqPageProps = {
    faq: { categories: FaqCategory[]; items: FaqEntry[] };
    cta: CtaBandBlock;
    copy: FaqCopy;
};
