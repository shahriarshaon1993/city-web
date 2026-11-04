import type { RowData } from '@tanstack/table-core';

declare module '@tanstack/table-core' {
    // The DataTable keeps its DataTableColumn in the column `meta` (types/table.ts).
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface ColumnMeta<TData extends RowData, TValue> {
        align?: 'left' | 'right';
        required?: boolean;
    }
}
