import { computed, onMounted, readonly, ref } from 'vue';
import { notify } from '@/lib/toast';

const STORAGE_KEY = 'gc.shortlist';
export const SHORTLIST_MAX = 20;

// One store for the whole page. It is only filled in the browser (onMounted),
// so the server render and hydration always see an empty, unavailable list.
const ids = ref<number[]>([]);
const available = ref(false);
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

        return;
    }

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

    function toggle(id: number): void {
        if (!available.value) {
            return;
        }

        const saved = has(id);

        if (!saved && ids.value.length >= SHORTLIST_MAX) {
            notify.info(
                `Your shortlist is full (${SHORTLIST_MAX} projects). Remove one to add another.`,
            );

            return;
        }

        try {
            write(
                saved
                    ? ids.value.filter((item) => item !== id)
                    : [...ids.value, id],
            );
        } catch {
            available.value = false;
        }
    }

    return {
        ids: readonly(ids),
        count: computed(() => ids.value.length),
        available: readonly(available),
        has,
        toggle,
    };
}
