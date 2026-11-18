import type { BlockBackground, BlockSpacing, PageBlockType } from './blocks';
import type { SeoForm } from './catalog';
import type { MenuOptions } from './menus';
import type { WorkflowState } from './publishing';

// ADM-SCR-06 pages and the page builder (CMP-ADM-BUILDER). Shapes of App\Queries\Admin\PageEditor,
// App\Http\Resources\Admin\PageRowResource and the preview endpoint of PageController.

export type PageTemplate = 'builder' | 'legal';

/**
 * A block as the editor holds it. `id` is the saved id, or a negative temporary one for a block that
 * is not saved yet: it is the block's identity in the list, the preview and the error map.
 */
export type EditorBlock = {
    id: number;
    type: PageBlockType;
    /** The editor shape of the type (asset ids, manual ids, limits), validated by its BlockSchema. */
    data: Record<string, any>;
    is_visible: boolean;
    anchor_id: string | null;
    background: BlockBackground;
    spacing: BlockSpacing;
    animate: boolean;
};

export type PageFormData = {
    title: string;
    slug: string;
    template: PageTemplate;
    last_reviewed_at: string | null;
    blocks: EditorBlock[];
    seo: SeoForm;
};

export type PageSummary = WorkflowState & {
    title: string;
    path: string;
    template: PageTemplate;
    is_system: boolean;
};

export type PageOption<T extends string | number = string | number> = {
    value: T;
    label: string;
    group?: string;
};

export type PageOptions = MenuOptions & {
    segments: PageOption<string>[];
    lifecycles: PageOption<string>[];
    projects: PageOption<number>[];
    areas: PageOption<number>[];
    cities: PageOption<string>[];
    testimonials: PageOption<number>[];
    faq_categories: PageOption<string>[];
    faqs: PageOption<number>[];
    news_categories: PageOption<string>[];
    offices: PageOption<number>[];
    partner_categories: PageOption<string>[];
    /** The Lucide names a feature card may use (FeatureCardsSchema::ICONS). */
    block_icons: string[];
    block_limit: number;
};

/** Messages of one block by path inside its data (`items.2.title`), from CMS-PB-003. */
export type BlockErrorMap = Record<string, string[]>;

/** The answer of `admin.pages.preview`. */
export type PreviewResult = {
    blocks: unknown[];
    starts_with_hero: boolean;
    /** Block id → its error map; a valid block is not listed. */
    errors: Record<string, BlockErrorMap>;
    checklist: import('./catalog').ChecklistItem[];
};

export type PageRow = {
    id: number;
    title: string;
    path: string;
    template: PageTemplate;
    is_system: boolean;
    blocks_count: number;
    status: {
        value: 'draft' | 'scheduled' | 'published' | 'archived';
        label: string;
    };
    pending: boolean;
    scheduled_at: string | null;
    updated_at: string | null;
    edit_url: string;
    public_url: string | null;
    can: {
        edit: boolean;
        publish: boolean;
        delete: boolean;
        duplicate: boolean;
    };
    delete_reason: string | null;
};
