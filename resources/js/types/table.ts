import type { Component } from 'vue';

/** `meta` prop of an AdminTable list (App\Queries\Admin\AdminTable::props). */
export type TableMeta = {
    total: number;
    page: number;
    per_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    /** The sort the server applied, `-` prefix = descending. */
    sort: string[];
};

/** What a saved view stores: the table's query string as an object (filter = name → value). */
export type ViewState = Record<string, string | Record<string, string>>;

/** Saved view (DM-VIEW) as sent in the `views` prop. */
export type TableViewItem = {
    id: number;
    name: string;
    is_shared: boolean;
    is_default: boolean;
    mine: boolean;
    owner: string;
    state: ViewState;
};

export type FilterOption = {
    value: string;
    label: string;
    count?: number;
    hint?: string;
};

/**
 * ADM-TBL-003 filter types. URL names: text/enum/boolean/relation → `filter[id]`,
 * date → `filter[id_from]` + `filter[id_to]` (YYYY-MM-DD), number → `filter[id_min]` + `filter[id_max]`.
 */
export type FilterDef = {
    id: string;
    label: string;
    kind: 'text' | 'enum' | 'boolean' | 'date' | 'number' | 'relation';
    options?: FilterOption[];
    /** relation: GET endpoint answering `?q=` with FilterOption[]. */
    optionsUrl?: string;
    placeholder?: string;
};

export type DataTableColumn = {
    id: string;
    label: string;
    sortable?: boolean;
    /** The server's sort name when it differs from the column id. */
    sortKey?: string;
    /** Locked visible in the column menu (ADM-TBL-005). */
    required?: boolean;
    /** Starts hidden. */
    hidden?: boolean;
    /** Default width in px; 80–600 (ADM-TBL-007). */
    width?: number;
    align?: 'left' | 'right';
};

export type DataTableRowAction = {
    id: string;
    label: string;
    icon?: Component;
    onSelect: () => void;
    destructive?: boolean;
    /** HELP-08: the action stays visible, disabled, and this sentence says why. */
    disabledReason?: string | null;
    hidden?: boolean;
};

/** What a bulk action receives (ADM-TBL-008/009). `all` = every row matching the filters. */
export type TableSelection = {
    ids: Array<string | number>;
    all: boolean;
    count: number;
    /** Server table parameters, to post along with `all`. */
    query: Record<string, string | Record<string, string>>;
};

export type DataTableBulkAction = {
    id: string;
    label: string;
    icon?: Component;
    destructive?: boolean;
    run: (selection: TableSelection) => void;
};
