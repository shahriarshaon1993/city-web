import { router } from '@inertiajs/vue3';
import type { ComputedRef, Ref } from 'vue';
import { computed, ref } from 'vue';
import { preferences } from '@/routes/admin/profile';
import type { Appearance, ResolvedAppearance } from '@/types';

export type UseAppearanceReturn = {
    appearance: Ref<Appearance>;
    resolvedAppearance: ComputedRef<ResolvedAppearance>;
    updateAppearance: (value: Appearance) => void;
};

const THEMES: Appearance[] = ['light', 'dark', 'system'];

// The server renders `data-appearance` from the user's saved theme (admin.blade.php),
// so this ref starts correct and never reads localStorage.
const appearance = ref<Appearance>('system');

const prefersDark = (): boolean =>
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-color-scheme: dark)').matches;

function applyClass(value: Appearance): void {
    if (typeof document === 'undefined') {
        return;
    }

    const dark = value === 'dark' || (value === 'system' && prefersDark());

    document.documentElement.classList.toggle('dark', dark);
}

function setCookie(name: string, value: string, days = 365): void {
    document.cookie = `${name}=${value};path=/;max-age=${days * 24 * 60 * 60};SameSite=Lax`;
}

export function initializeTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    const fromServer = document.documentElement.dataset.appearance as
        | Appearance
        | undefined;

    appearance.value =
        fromServer && THEMES.includes(fromServer) ? fromServer : 'system';
    applyClass(appearance.value);

    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', () => {
            if (appearance.value === 'system') {
                applyClass('system');
            }
        });
}

export function useAppearance(): UseAppearanceReturn {
    const resolvedAppearance = computed<ResolvedAppearance>(() =>
        appearance.value === 'system'
            ? prefersDark()
                ? 'dark'
                : 'light'
            : appearance.value,
    );

    /** Applies at once (cookie + class), then saves to the user's account so other devices match. */
    function updateAppearance(value: Appearance): void {
        appearance.value = value;
        document.documentElement.dataset.appearance = value;
        setCookie('appearance', value);
        applyClass(value);

        router.patch(
            preferences.url(),
            { theme: value },
            { preserveScroll: true, preserveState: true, only: ['auth'] },
        );
    }

    return { appearance, resolvedAppearance, updateAppearance };
}
