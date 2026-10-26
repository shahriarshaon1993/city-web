import { router } from '@inertiajs/vue3';
import type { InjectionKey, Ref } from 'vue';
import { inject, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { emptyFilters, listingUrl, stateFromMeta } from '@/lib/listing';
import type { ListingFacets, ListingMeta, ListingState } from '@/types/listing';

/** FR-SRCH-003 (+ the header and head, which change with the landing page). */
const RELOAD_ONLY = ['projects', 'facets', 'meta', 'listing', 'seo'];

const TYPING_DEBOUNCE = 350;
/** MOT-SKELETON: shown after 300 ms, then for at least 400 ms. */
const SKELETON_DELAY = 300;
const SKELETON_MIN = 400;

/**
 * UX-08 filter flow for PG-02/03. `state` is the controls' model; every
 * change becomes the state's one URL (lib/listing.ts) and a partial reload:
 * `push` for selections (Back undoes them), `replace` + 350 ms debounce for
 * typing. The server's `meta` re-syncs the state after each response and on
 * Back/Forward. Focus returns to the changed control when its element was
 * re-rendered (AC-SRCH-02).
 */
export function useProjectListing(source: {
    meta: Ref<ListingMeta>;
    facets: Ref<ListingFacets>;
}) {
    const state = reactive<ListingState>(stateFromMeta(source.meta.value));
    const pending = ref(false);
    const skeleton = ref(false);

    let visitId = 0;
    let typingTimer: ReturnType<typeof setTimeout> | undefined;
    let skeletonTimer: ReturnType<typeof setTimeout> | undefined;
    let skeletonShownAt = 0;

    watch(source.meta, (meta) => {
        const next = stateFromMeta(meta);

        // Keep what the visitor is still typing.
        if (typingTimer !== undefined) {
            next.q = state.q;
        }

        Object.assign(state, next);
    });

    function urlFor(patch: Partial<ListingState>): string {
        return listingUrl(
            { ...state, area: [...state.area], ...patch },
            source.facets.value,
            source.meta.value.base_url,
        );
    }

    function startSkeleton(): void {
        clearTimeout(skeletonTimer);
        skeletonTimer = setTimeout(() => {
            skeleton.value = true;
            skeletonShownAt = performance.now();
        }, SKELETON_DELAY);
    }

    function stopSkeleton(): void {
        clearTimeout(skeletonTimer);

        if (!skeleton.value) {
            return;
        }

        const remaining = SKELETON_MIN - (performance.now() - skeletonShownAt);
        skeletonTimer = setTimeout(
            () => {
                skeleton.value = false;
            },
            Math.max(0, remaining),
        );
    }

    function visit(url: string, replace: boolean): void {
        const id = ++visitId;
        const focusKey = (document.activeElement as HTMLElement | null)
            ?.closest<HTMLElement>('[data-filter-key]')
            ?.getAttribute('data-filter-key');

        router.visit(url, {
            only: RELOAD_ONLY,
            preserveState: true,
            preserveScroll: true,
            replace,
            onStart: () => {
                pending.value = true;
                startSkeleton();
            },
            onSuccess: () => {
                if (
                    focusKey &&
                    (document.activeElement === document.body ||
                        !document.activeElement?.isConnected)
                ) {
                    document
                        .querySelector<HTMLElement>(
                            `[data-filter-key="${CSS.escape(focusKey)}"]`,
                        )
                        ?.focus({ preventScroll: true });
                }
            },
            onFinish: () => {
                if (id === visitId) {
                    pending.value = false;
                    stopSkeleton();
                }
            },
        });
    }

    /** A discrete selection: new history entry. */
    function update(patch: Partial<ListingState>): void {
        clearTimeout(typingTimer);
        typingTimer = undefined;
        Object.assign(state, patch);
        visit(urlFor({}), false);
    }

    /** Typing in the keyword field. */
    function type(q: string): void {
        state.q = q;
        clearTimeout(typingTimer);
        typingTimer = setTimeout(() => {
            typingTimer = undefined;
            visit(urlFor({}), true);
        }, TYPING_DEBOUNCE);
    }

    /** Submit/Enter in the keyword field: apply now. */
    function flushTyping(): void {
        if (typingTimer !== undefined) {
            clearTimeout(typingTimer);
            typingTimer = undefined;
            visit(urlFor({}), true);
        }
    }

    function clearAll(): void {
        update(emptyFilters());
    }

    /** Pagination: same reload, then back to the top of the results. */
    function goToPage(url: string): void {
        router.visit(url, {
            only: RELOAD_ONLY,
            preserveState: true,
            onStart: () => {
                pending.value = true;
                startSkeleton();
            },
            onFinish: () => {
                pending.value = false;
                stopSkeleton();
            },
        });
    }

    onBeforeUnmount(() => {
        clearTimeout(typingTimer);
        clearTimeout(skeletonTimer);
    });

    return {
        state,
        pending,
        skeleton,
        meta: source.meta,
        facets: source.facets,
        urlFor,
        update,
        type,
        flushTyping,
        clearAll,
        goToPage,
    };
}

export type ProjectListing = ReturnType<typeof useProjectListing>;

export const projectListingKey: InjectionKey<ProjectListing> =
    Symbol('projectListing');

export function useListing(): ProjectListing {
    const listing = inject(projectListingKey);

    if (!listing) {
        throw new Error('useListing() needs the Projects page provider.');
    }

    return listing;
}
