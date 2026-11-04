import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * UI hiding only (ARC-005): the server answers 403 whatever the UI shows.
 * `can('a')` / `canAny('a', 'b')` read `auth.permissions`.
 */
export function useCan() {
    const page = usePage();
    const granted = computed(() => new Set(page.props.auth?.permissions ?? []));

    return {
        can: (permission: string): boolean => granted.value.has(permission),
        canAny: (...permissions: string[]): boolean =>
            permissions.some((permission) => granted.value.has(permission)),
    };
}
