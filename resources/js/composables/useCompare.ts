import { computed, onMounted, readonly, ref } from 'vue';
import { notify } from '@/lib/toast';
import type { MediaImage, ProjectCard } from '@/types/site';

const STORAGE_KEY = 'gc.compare';
/** FR-SHORT-003, same as DemoShortlist::COMPARE_MAX. */
export const COMPARE_MAX = 3;

/** What the compare bar shows for a ticked project. */
export type CompareItem = {
    id: number;
    name: string;
    area: string;
    cover: MediaImage | null;
};

// One selection for the whole tab. Kept in sessionStorage (a comparison is
// short-lived, owner 8.3) and in memory when storage is blocked, so the
// checkboxes work either way. Filled in onMounted only (SEO-REN-08): the
// server render and hydration see no selection and no checkboxes.
const items = ref<CompareItem[]>([]);
const ready = ref(false);
let started = false;

function isItem(value: unknown): value is CompareItem {
    const item = value as CompareItem;

    return (
        typeof item === 'object' &&
        item !== null &&
        Number.isInteger(item.id) &&
        item.id > 0 &&
        typeof item.name === 'string' &&
        typeof item.area === 'string'
    );
}

function parse(raw: string | null): CompareItem[] {
    try {
        const value: unknown = JSON.parse(raw ?? '[]');

        return Array.isArray(value)
            ? value.filter(isItem).slice(0, COMPARE_MAX)
            : [];
    } catch {
        return [];
    }
}

function write(next: CompareItem[]): void {
    items.value = next;

    try {
        window.sessionStorage.setItem(STORAGE_KEY, JSON.stringify(next));
    } catch {
        // Blocked storage: the selection lives until the tab reloads.
    }
}

function start(): void {
    if (started) {
        return;
    }

    started = true;

    try {
        items.value = parse(window.sessionStorage.getItem(STORAGE_KEY));
    } catch {
        items.value = [];
    }

    ready.value = true;
}

function toItem(project: ProjectCard): CompareItem {
    return {
        id: project.id,
        name: project.name,
        area: project.location.area,
        cover: project.cover,
    };
}

/**
 * FR-SHORT-003: the "Compare" checkboxes and the floating compare bar, max 3.
 * A 4th tick is refused with a toast (AC-SHORT-01).
 */
export function useCompare() {
    onMounted(start);

    function has(id: number): boolean {
        return items.value.some((item) => item.id === id);
    }

    /** Returns whether the project is selected afterwards. */
    function toggle(project: ProjectCard): boolean {
        if (has(project.id)) {
            remove(project.id);

            return false;
        }

        if (items.value.length >= COMPARE_MAX) {
            notify.info(`You can compare up to ${COMPARE_MAX} projects`);

            return false;
        }

        write([...items.value, toItem(project)]);

        return true;
    }

    function remove(id: number): void {
        write(items.value.filter((item) => item.id !== id));
    }

    function clear(): void {
        write([]);
    }

    /** PG-21: keep the projects the server found (FR-SHORT-002 for compare), in its order. */
    function replace(projects: ProjectCard[]): void {
        write(projects.slice(0, COMPARE_MAX).map(toItem));
    }

    return {
        items: readonly(items),
        ids: computed(() => items.value.map((item) => item.id)),
        count: computed(() => items.value.length),
        ready: readonly(ready),
        has,
        toggle,
        remove,
        clear,
        replace,
    };
}
