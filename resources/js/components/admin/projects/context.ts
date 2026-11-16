import type { InertiaForm } from '@inertiajs/vue3';
import type { InjectionKey } from 'vue';
import { inject, reactive } from 'vue';
import type { ProjectFormData, ProjectOptions } from '@/types';

// What every tab of the project editor shares: the page's form, the option lists and the helpers
// that keep field ids, error counts and card state the same everywhere.
export type ProjectEditorContext = {
    form: InertiaForm<ProjectFormData>;
    options: ProjectOptions;
    /** Server errors by key (`unit_types.2.size_sft`). */
    errors: Record<string, string | undefined>;
    /** The project is saved and live, so a slug change redirects the old URL. */
    published: boolean;
    projectId: number | null;
    canEdit: boolean;
};

export const projectEditorKey: InjectionKey<ProjectEditorContext> =
    Symbol('project-editor');

export function useProjectEditor(): ProjectEditorContext {
    const context = inject(projectEditorKey);

    if (!context) {
        throw new Error('The project editor must provide its context.');
    }

    return context;
}

/**
 * A live view of `form.errors` that stays valid after a destructure: Inertia replaces the errors
 * object on every response, so a tab that keeps `errors` must read through this proxy.
 */
export function errorView(form: {
    errors: Record<string, string | undefined>;
}): Record<string, string | undefined> {
    return new Proxy({} as Record<string, string | undefined>, {
        get: (_target, key) =>
            typeof key === 'string' ? form.errors[key] : undefined,
        has: (_target, key) => typeof key === 'string' && key in form.errors,
        ownKeys: () => Object.keys(form.errors),
        getOwnPropertyDescriptor: (_target, key) =>
            typeof key === 'string' && key in form.errors
                ? {
                      enumerable: true,
                      configurable: true,
                      value: form.errors[key],
                  }
                : undefined,
    });
}

/** `fid('unit_types', 2, 'size_sft')` → `field-unit_types-2-size_sft`: the id of that control. */
export function fid(...parts: Array<string | number>): string {
    return `field-${parts.join('-')}`;
}

/** The control id of a server error key, for the error summary links. */
export function fieldIdOfError(key: string): string {
    if (key.startsWith('seo.')) {
        const field = key.slice(4).replace('og_image_asset_id', 'og-image');

        return `seo-${field.replace(/_/g, '-')}`;
    }

    return fid(...key.split('.'));
}

/** How many server errors lie under a key prefix (VAL-07: the number on a card or tab). */
export function countErrors(
    errors: Record<string, string | undefined>,
    prefix: string,
): number {
    return Object.keys(errors).filter(
        (key) => key === prefix || key.startsWith(`${prefix}.`),
    ).length;
}

/** The editor tab a server error belongs to. */
export function tabOfError(key: string): string {
    const rules: Array<[RegExp, string]> = [
        [/^unit_types/, 'units'],
        [/^amenity_ids/, 'amenities'],
        [/^progress_updates/, 'progress'],
        [/^nearby_places/, 'nearby'],
        [/^(documents|brochure_gated)/, 'documents'],
        [/^(show_price|price_)/, 'pricing'],
        [/^(default_assignee_id|whatsapp_override)/, 'routing'],
        [/^seo/, 'seo'],
        [/^(overview|design_narrative|highlights)/, 'content'],
        [/^(cover_asset_id|gallery|video_|virtual_tour_url)/, 'media'],
        [
            /^(address|latitude|longitude|land_area_katha|storeys|total_units|units_per_floor|lifts|car_parks|basements|road_width_ft|facing|orientation|launch_date|handover_date|rajuk_approval_no|architect|structural_engineer|contractor|custom_facts)/,
            'facts',
        ],
    ];

    return rules.find(([pattern]) => pattern.test(key))?.[1] ?? 'basics';
}

/** Open / closed state of repeater cards; a card with errors starts open (VAL-07). */
export function useCardState() {
    const state = reactive<Record<string, boolean>>({});

    return {
        isOpen: (key: string, errorCount = 0): boolean =>
            state[key] ?? errorCount > 0,
        set: (key: string, open: boolean): void => {
            state[key] = open;
        },
    };
}

/** Moves `index` by `delta` inside `list` (a no-op at the ends). */
export function moveItem<T>(list: T[], index: number, delta: -1 | 1): T[] {
    const target = index + delta;

    if (target < 0 || target >= list.length) {
        return list;
    }

    const next = [...list];
    [next[index], next[target]] = [next[target], next[index]];

    return next;
}
