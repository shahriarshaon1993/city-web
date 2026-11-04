import { formatDate } from '@/lib/format';
import type { FilterDef } from '@/types';

// How a FilterDef maps onto the URL's `filter[...]` keys (see types/table.ts) and how an active
// filter reads in its chip (ADM-TBL-003).

export function filterKeys(def: FilterDef): string[] {
    switch (def.kind) {
        case 'date':
            return [`${def.id}_from`, `${def.id}_to`];
        case 'number':
            return [`${def.id}_min`, `${def.id}_max`];
        default:
            return [def.id];
    }
}

export function isFilterActive(
    def: FilterDef,
    filters: Record<string, string>,
): boolean {
    return filterKeys(def).some((key) => (filters[key] ?? '') !== '');
}

/** Chip text for an active filter, or null when it is not set. */
export function describeFilter(
    def: FilterDef,
    filters: Record<string, string>,
    relationLabels: Record<string, string> = {},
): string | null {
    if (!isFilterActive(def, filters)) {
        return null;
    }

    switch (def.kind) {
        case 'date': {
            const from = filters[`${def.id}_from`];
            const to = filters[`${def.id}_to`];

            return `${def.label}: ${from ? formatDate(from) : 'any'} – ${to ? formatDate(to) : 'any'}`;
        }
        case 'number': {
            const min = filters[`${def.id}_min`];
            const max = filters[`${def.id}_max`];

            return `${def.label}: ${min || 'any'} – ${max || 'any'}`;
        }
        case 'boolean':
            return `${def.label}: ${filters[def.id] === '1' ? 'Yes' : 'No'}`;
        case 'enum':
            return `${def.label}: ${filters[def.id]
                .split(',')
                .map(
                    (value) =>
                        def.options?.find((option) => option.value === value)
                            ?.label ?? value,
                )
                .join(', ')}`;
        case 'relation':
            return `${def.label}: ${filters[def.id]
                .split(',')
                .map((value) => relationLabels[value] ?? `#${value}`)
                .join(', ')}`;
        default:
            return `${def.label}: ${filters[def.id]}`;
    }
}
