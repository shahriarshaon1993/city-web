import type {
    LinkState,
    MenuColumn,
    MenuLink,
    MenuLinkType,
    MenuNode,
} from '@/types';

// CMS-NAV-005: the pure tree operations behind the menu editor (indent, outdent, move, remove)
// plus hydration and the payload `SaveMenu` takes. Nothing here touches the DOM, so the
// drag-and-drop list and the keyboard actions share the same rules.

let counter = 0;

export function uid(): string {
    counter += 1;

    return `n${counter}`;
}

const ENTITY_TYPES: MenuLinkType[] = [
    'page',
    'project',
    'location',
    'post',
    'category',
];

export function isEntityType(type: MenuLinkType): boolean {
    return ENTITY_TYPES.includes(type);
}

// --- Build ------------------------------------------------------------------------------------

export function blankLink(): MenuLink {
    return {
        key: uid(),
        label: '',
        link_type: 'url',
        linkable_id: null,
        url: '',
        anchor: null,
        new_tab: false,
        link: null,
    };
}

export function blankNode(): MenuNode {
    return {
        ...blankLink(),
        id: null,
        icon: null,
        description: null,
        is_mega: false,
        mega_columns: [],
        children: [],
    };
}

export function blankColumn(): MenuColumn {
    return { key: uid(), kind: 'links', heading: null, items: [] };
}

/** Adds the client keys to what the server sent. */
export function hydrate(nodes: MenuNode[]): MenuNode[] {
    return nodes.map((node) => ({
        ...node,
        key: uid(),
        children: hydrate(node.children),
        mega_columns: node.mega_columns.map((column) => ({
            ...column,
            key: uid(),
            items: column.items.map((link) => ({ ...link, key: uid() })),
        })),
    }));
}

/** The body of the save request: ids and fields, no client state. */
export function toPayload(nodes: MenuNode[]): Record<string, unknown>[] {
    const link = (item: MenuLink) => ({
        label: item.label.trim(),
        link_type: item.link_type,
        linkable_id: isEntityType(item.link_type) ? item.linkable_id : null,
        url: isEntityType(item.link_type) ? null : item.url,
        anchor: item.link_type === 'anchor' ? item.anchor : null,
        new_tab: item.new_tab,
    });

    return nodes.map((node) => ({
        id: node.id,
        ...link(node),
        icon: node.icon || null,
        description: node.description || null,
        is_mega: node.is_mega,
        mega_columns: node.is_mega
            ? node.mega_columns.map((column) => ({
                  kind: column.kind,
                  heading: column.heading || null,
                  items: column.kind === 'links' ? column.items.map(link) : [],
              }))
            : [],
        children: toPayload(node.children),
    }));
}

export function cloneTree(nodes: MenuNode[]): MenuNode[] {
    return JSON.parse(JSON.stringify(nodes)) as MenuNode[];
}

/** A copy for the dialog; new keys, so it never aliases the tree. */
export function cloneNode(node: MenuNode): MenuNode {
    return hydrate([JSON.parse(JSON.stringify(node)) as MenuNode])[0];
}

// --- Find -------------------------------------------------------------------------------------

export type Location = {
    list: MenuNode[];
    index: number;
    parent: MenuNode | null;
    /** 1 = top level. */
    depth: number;
};

export function locate(
    roots: MenuNode[],
    key: string,
    parent: MenuNode | null = null,
    depth = 1,
): Location | null {
    for (let index = 0; index < roots.length; index += 1) {
        const node = roots[index];

        if (node.key === key) {
            return { list: roots, index, parent, depth };
        }

        const found = locate(node.children, key, node, depth + 1);

        if (found) {
            return found;
        }
    }

    return null;
}

export function countNodes(nodes: MenuNode[]): number {
    return nodes.reduce(
        (total, node) => total + 1 + countNodes(node.children),
        0,
    );
}

export function flatten(nodes: MenuNode[]): MenuNode[] {
    return nodes.flatMap((node) => [node, ...flatten(node.children)]);
}

// --- Rules ------------------------------------------------------------------------------------

export type TreeLimits = {
    maxDepth: number;
    maxItems: number;
    maxChildren: number;
};

/** Why an action is not possible right now (HELP-08), or null when it is. */
export function moveUpReason(roots: MenuNode[], key: string): string | null {
    const spot = locate(roots, key);

    return spot && spot.index > 0 ? null : 'It is already first in its list.';
}

export function moveDownReason(roots: MenuNode[], key: string): string | null {
    const spot = locate(roots, key);

    return spot && spot.index < spot.list.length - 1
        ? null
        : 'It is already last in its list.';
}

export function indentReason(
    roots: MenuNode[],
    key: string,
    limits: TreeLimits,
): string | null {
    const spot = locate(roots, key);

    if (!spot) {
        return 'Item not found.';
    }

    if (limits.maxDepth < 2) {
        return 'This menu has one level only.';
    }

    if (spot.index === 0) {
        return 'There is no item above to nest it under.';
    }

    const node = spot.list[spot.index];
    const above = spot.list[spot.index - 1];

    if (spot.depth >= limits.maxDepth) {
        return 'Menus go two levels deep at most.';
    }

    if (node.children.length > 0) {
        return 'It has sub-items. Move them out first.';
    }

    if (node.is_mega) {
        return 'A mega menu stays at the top level.';
    }

    if (above.is_mega) {
        return `“${above.label || 'The item above'}” opens a mega menu, which replaces sub-items.`;
    }

    if (above.children.length >= limits.maxChildren) {
        return `An item holds ${limits.maxChildren} sub-items at most.`;
    }

    return null;
}

export function outdentReason(
    roots: MenuNode[],
    key: string,
    limits: TreeLimits,
): string | null {
    const spot = locate(roots, key);

    if (!spot || spot.depth < 2) {
        return 'It is already at the top level.';
    }

    const top = locate(roots, spot.parent?.key ?? '');

    return top && top.list.length >= limits.maxItems
        ? `The top level holds ${limits.maxItems} items at most.`
        : null;
}

export function canAddChild(
    node: MenuNode,
    depth: number,
    limits: TreeLimits,
): string | null {
    if (depth >= limits.maxDepth) {
        return limits.maxDepth < 2
            ? 'This menu has one level only.'
            : 'Menus go two levels deep at most.';
    }

    if (node.is_mega) {
        return 'A mega menu replaces sub-items.';
    }

    if (node.children.length >= limits.maxChildren) {
        return `An item holds ${limits.maxChildren} sub-items at most.`;
    }

    return null;
}

// --- Change -----------------------------------------------------------------------------------

export function moveUp(roots: MenuNode[], key: string): boolean {
    const spot = locate(roots, key);

    if (!spot || spot.index === 0) {
        return false;
    }

    const [node] = spot.list.splice(spot.index, 1);

    spot.list.splice(spot.index - 1, 0, node);

    return true;
}

export function moveDown(roots: MenuNode[], key: string): boolean {
    const spot = locate(roots, key);

    if (!spot || spot.index >= spot.list.length - 1) {
        return false;
    }

    const [node] = spot.list.splice(spot.index, 1);

    spot.list.splice(spot.index + 1, 0, node);

    return true;
}

export function indent(
    roots: MenuNode[],
    key: string,
    limits: TreeLimits,
): boolean {
    if (indentReason(roots, key, limits) !== null) {
        return false;
    }

    const spot = locate(roots, key);

    if (!spot) {
        return false;
    }

    const [node] = spot.list.splice(spot.index, 1);

    spot.list[spot.index - 1].children.push(node);

    return true;
}

export function outdent(
    roots: MenuNode[],
    key: string,
    limits: TreeLimits,
): boolean {
    if (outdentReason(roots, key, limits) !== null) {
        return false;
    }

    const spot = locate(roots, key);
    const parent = spot?.parent ?? null;
    const home = parent ? locate(roots, parent.key) : null;

    if (!spot || !parent || !home) {
        return false;
    }

    const [node] = spot.list.splice(spot.index, 1);

    home.list.splice(home.index + 1, 0, node);

    return true;
}

export function remove(roots: MenuNode[], key: string): boolean {
    const spot = locate(roots, key);

    if (!spot) {
        return false;
    }

    spot.list.splice(spot.index, 1);

    return true;
}

// --- Status -----------------------------------------------------------------------------------

export function linkStatus(link: MenuLink): LinkState['status'] {
    if (!isEntityType(link.link_type)) {
        return 'ok';
    }

    if (link.linkable_id === null) {
        return 'missing';
    }

    return link.link?.status ?? 'ok';
}

/** Links of the whole tree the public site would hide. */
export function hiddenCount(nodes: MenuNode[]): number {
    let hidden = 0;

    for (const node of flatten(nodes)) {
        hidden += linkStatus(node) === 'ok' ? 0 : 1;

        if (node.is_mega) {
            for (const column of node.mega_columns) {
                for (const link of column.items) {
                    hidden += linkStatus(link) === 'ok' ? 0 : 1;
                }
            }
        }
    }

    return hidden;
}

// --- Client-side check (VAL-03: the same rules and words as SaveMenuRequest) -------------------

const URL_PATTERN = /^(\/(?!\/)|https?:\/\/|mailto:|tel:)/i;

export type LinkErrors = Partial<
    Record<'label' | 'target' | 'url' | 'anchor', string>
>;

export function linkErrors(link: MenuLink): LinkErrors {
    const errors: LinkErrors = {};
    const url = (link.url ?? '').trim();

    if (link.label.trim() === '') {
        errors.label = 'Enter a label.';
    }

    if (isEntityType(link.link_type)) {
        if (link.linkable_id === null) {
            errors.target = 'Choose what it links to.';
        }

        return errors;
    }

    if (link.link_type === 'url' && url === '') {
        errors.url = 'Enter an address, e.g. /projects or https://example.com.';
    } else if (url !== '' && !URL_PATTERN.test(url)) {
        errors.url =
            'Start with / (a page on this site, e.g. /projects) or https://.';
    }

    if (link.link_type === 'anchor') {
        const anchor = (link.anchor ?? '').trim();

        if (anchor === '') {
            errors.anchor = 'Enter the section id, e.g. enquire.';
        } else if (!/^#?[A-Za-z][A-Za-z0-9_-]*$/.test(anchor)) {
            errors.anchor =
                'Use letters, numbers, - and _ only, starting with a letter.';
        }
    }

    return errors;
}

// --- Errors -----------------------------------------------------------------------------------

/** `items.2.children.1.label` → the row `items.2.children.1`. */
export function rowPathOfError(key: string): string | null {
    const match = key.match(/^(items\.\d+(?:\.children\.\d+)?)/);

    return match ? match[1] : null;
}

export function rowDomId(path: string): string {
    return `menu-row-${path.replaceAll('.', '-')}`;
}

/** Errors that belong to the item itself (its fields and mega columns), not its sub-items. */
export function ownErrors(
    errors: Record<string, string | undefined>,
    path: string,
): string[] {
    return Object.entries(errors)
        .filter(
            ([key, message]) =>
                !!message &&
                (key === path || key.startsWith(`${path}.`)) &&
                !key.slice(path.length).startsWith('.children.'),
        )
        .map(([, message]) => String(message));
}

export function errorsWithin(
    errors: Record<string, string | undefined>,
    path: string,
): number {
    return Object.entries(errors).filter(
        ([key, message]) =>
            !!message && (key === path || key.startsWith(`${path}.`)),
    ).length;
}
