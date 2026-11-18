// ADM-SCR-08 News admin (16.3): the post editor, the lists of posts, categories and tags. Shapes of
// PostEditor, PostRowResource, PostCategoryRowResource and TagRowResource.
import type { AssetMap, ChecklistItem, SeoForm, WorkflowState } from '@/types';

export type PostFormData = {
    title: string;
    slug: string;
    excerpt: string;
    body: string;
    cover_asset_id: number | null;
    category_id: number | null;
    author_user_id: number | null;
    is_featured: boolean;
    tags: string[];
    project_ids: number[];
    seo: SeoForm;
};

export type PostSummary = WorkflowState & {
    title: string;
    path: string;
    read_minutes: number;
};

export type PostOption = { value: number; label: string };

export type PostOptions = {
    categories: PostOption[];
    authors: PostOption[];
    projects: PostOption[];
    tag_suggestions: string[];
    limits: {
        title: number;
        excerpt: number;
        tags: number;
        projects: number;
        cover_min_width: number;
    };
};

export type PostEditProps = {
    post: PostSummary | null;
    form: PostFormData;
    assets: AssetMap;
    options: PostOptions;
    checklist: ChecklistItem[];
    lastSaved: string | null;
    can: {
        publish: boolean;
        delete: boolean;
        duplicate: boolean;
        update: boolean;
    };
};

export type PostRow = {
    id: number;
    title: string;
    path: string;
    category: string | null;
    author: string | null;
    is_featured: boolean;
    status: {
        value: 'draft' | 'scheduled' | 'published' | 'archived';
        label: string;
    };
    pending: boolean;
    scheduled_at: string | null;
    published_at: string | null;
    updated_at: string | null;
    edit_url: string;
    public_url: string | null;
    can: {
        edit: boolean;
        publish: boolean;
        delete: boolean;
        duplicate: boolean;
    };
};

export type LinkedItem = { label: string; href: string };

export type PostCategoryRow = {
    id: number;
    name: string;
    slug: string;
    path: string;
    description: string | null;
    posts_count: number;
    posts: LinkedItem[];
    can: { edit: boolean; delete: boolean };
};

export type TagRow = {
    id: number;
    name: string;
    slug: string;
    path: string;
    posts_count: number;
    can: { edit: boolean; delete: boolean };
};
