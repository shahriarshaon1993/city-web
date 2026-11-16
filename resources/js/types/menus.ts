// Admin menu editor (ADM-SCR-12, CMS-NAV-*). Shapes of App\Queries\Admin\MenuEditor plus the
// client-only `key` that lets the tree keep an item's identity while it is dragged.

export type MenuLinkType =
    | 'page'
    | 'project'
    | 'location'
    | 'post'
    | 'category'
    | 'url'
    | 'anchor';

export type MegaKind = 'links' | 'segments' | 'locations' | 'featured_projects';

/** CMS-NAV-004: what the public site does with the link. */
export type LinkStatus = 'ok' | 'unpublished' | 'missing';

export type LinkTargetInfo = {
    id: number;
    label: string;
    url: string;
    published: boolean;
    hint: string | null;
};

export type LinkState = {
    status: LinkStatus;
    url: string | null;
    target: LinkTargetInfo | null;
};

/** A link without a place in the tree: a menu item's own link, or a link inside a mega column. */
export type MenuLink = {
    key: string;
    label: string;
    link_type: MenuLinkType;
    linkable_id: number | null;
    url: string | null;
    anchor: string | null;
    new_tab: boolean;
    /** How the site resolves it now; the picker updates it when the target changes. */
    link: LinkState | null;
};

export type MenuColumn = {
    key: string;
    kind: MegaKind;
    heading: string | null;
    items: MenuLink[];
};

export type MenuNode = MenuLink & {
    /** null = not saved yet. */
    id: number | null;
    icon: string | null;
    description: string | null;
    is_mega: boolean;
    mega_columns: MenuColumn[];
    children: MenuNode[];
};

export type MenuTab = {
    key: string;
    label: string;
    href: string;
    /** Links the public site hides in this menu. */
    hidden: number;
};

export type MenuInfo = {
    key: string;
    name: string;
    appears_in: string;
    is_footer_column: boolean;
    supports_mega: boolean;
    max_depth: number;
    max_items: number;
    max_children: number;
    items: MenuNode[];
};

export type MenuOptions = {
    link_types: { value: MenuLinkType; label: string; entity: boolean }[];
    icons: string[];
};

export const MENU_LIMITS = {
    label: 30,
    description: 80,
    url: 500,
    anchor: 42,
    heading: 30,
    megaColumns: 4,
    megaLinks: 8,
} as const;
