import { reactive } from 'vue';
import type { FilterOption } from '@/types';

// Labels of the options picked in relation filters (ADM-TBL-003 async combobox), shared by the
// filter popover and the chips. Ids from a copied URL are resolved once through `?ids=`.
const labels = reactive<Record<string, Record<string, string>>>({});

export function relationLabels(endpoint: string): Record<string, string> {
    return (labels[endpoint] ??= {});
}

export function rememberOptions(
    endpoint: string,
    options: FilterOption[],
): void {
    const known = relationLabels(endpoint);

    for (const option of options) {
        known[option.value] = option.label;
    }
}

export async function fetchOptions(
    endpoint: string,
    params: Record<string, string>,
    signal?: AbortSignal,
): Promise<FilterOption[]> {
    const response = await fetch(
        `${endpoint}?${new URLSearchParams(params).toString()}`,
        {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal,
        },
    );

    if (!response.ok) {
        throw new Error(`Options request failed: ${response.status}`);
    }

    const options = (await response.json()) as FilterOption[];

    rememberOptions(endpoint, options);

    return options;
}

export async function resolveLabels(
    endpoint: string,
    ids: string[],
): Promise<void> {
    const known = relationLabels(endpoint);
    const missing = ids.filter((id) => !(id in known));

    if (missing.length > 0) {
        await fetchOptions(endpoint, { ids: missing.join(',') }).catch(
            () => undefined,
        );
    }
}
