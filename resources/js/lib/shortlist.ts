// PG-20 / PG-21 URLs (FR-SHORT-002/003): the IDs travel as one readable
// comma list (`?ids=3,1,7`), not Wayfinder's encoded query (`%2C`).
import { compare, shortlist } from '@/routes/site';

function withIds(base: string, ids: readonly number[]): string {
    return ids.length > 0 ? `${base}?ids=${ids.join(',')}` : base;
}

/** Newest first: the page lists the last saved project at the top. */
export function shortlistHref(ids: readonly number[]): string {
    return withIds(shortlist.url(), [...ids].reverse());
}

export function compareHref(ids: readonly number[]): string {
    return withIds(compare.url(), ids);
}
