import { computed, onMounted, readonly, ref } from 'vue';
import { track } from '@/lib/analytics';
import { notify } from '@/lib/toast';

const STORAGE_KEY = 'gc.shortlist';
export const SHORTLIST_MAX = 20;

// One store for the whole page. It is only filled in the browser (onMounted),
// so the server render and hydration always see an empty, unavailable list.
const ids = ref<number[]>([]);
const available = ref(false);
/** True once storage was tried (available or not): PG-20 waits for it. */
const ready = ref(false);
let started = false;

function parse(raw: string | null): number[] {
    try {
        const value: unknown = JSON.parse(raw ?? '[]');

        if (!Array.isArray(value)) {
            return [];
        }

        const valid = value.filter(
            (id): id is number => Number.isInteger(id) && id > 0,
        );

        return [...new Set(valid)].slice(0, SHORTLIST_MAX);
    } catch {
        return [];
    }
}

/** Throws when storage is blocked or full (private mode), see FR-SHORT-005. */
function write(next: number[]): void {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    ids.value = next;
}

function start(): void {
    if (started) {
        return;
    }

    started = true;

    try {
        const probe = `${STORAGE_KEY}.probe`;
        window.localStorage.setItem(probe, '1');
        window.localStorage.removeItem(probe);
        ids.value = parse(window.localStorage.getItem(STORAGE_KEY));
        available.value = true;
    } catch {
        available.value = false;
        ready.value = true;

        return;
    }

    ready.value = true;

    // Keeps hearts and the header count in step across tabs.
    window.addEventListener('storage', (event) => {
        if (event.key === STORAGE_KEY || event.key === null) {
            ids.value = parse(event.newValue);
        }
    });
}

/**
 * FR-SHORT-001: project IDs in `localStorage['gc.shortlist']`, max 20.
 * FR-SHORT-005: `available` stays false when storage throws; callers hide
 * the hearts and nothing is logged. A full list refuses the 21st (DEC-44).
 */
export function useShortlist() {
    onMounted(start);

    function has(id: number): boolean {
        return ids.value.includes(id);
    }

    /** Writes the list; false when storage refused it (FR-SHORT-005). */
    function save(next: number[]): boolean {
        try {
            write(next);

            return true;
        } catch {
            available.value = false;

            return false;
        }
    }

    /** `at` puts it back where it was (Undo on PG-20); else it goes last. */
    function add(id: number, slug: string | null = null, at?: number): void {
        if (!available.value || has(id)) {
            return;
        }

        if (ids.value.length >= SHORTLIST_MAX) {
            notify.info(
                `Your shortlist is full (${SHORTLIST_MAX} projects). Remove one to add another.`,
            );

            return;
        }

        const next = [...ids.value];
        next.splice(at ?? next.length, 0, id);

        if (save(next)) {
            track('shortlist_add', { project_slug: slug });
        }
    }

    function remove(id: number): void {
        if (available.value && has(id)) {
            save(ids.value.filter((item) => item !== id));
        }
    }

    function toggle(id: number, slug: string | null = null): void {
        if (has(id)) {
            remove(id);
        } else {
            add(id, slug);
        }
    }

    /** FR-SHORT-002: drops IDs the server no longer knows (unknown or unpublished). */
    function keepOnly(known: readonly number[]): void {
        const next = ids.value.filter((id) => known.includes(id));

        if (available.value && next.length !== ids.value.length) {
            save(next);
        }
    }

    return {
        ids: readonly(ids),
        count: computed(() => ids.value.length),
        available: readonly(available),
        ready: readonly(ready),
        has,
        add,
        remove,
        toggle,
        keepOnly,
    };
}
