import { usePage } from '@inertiajs/vue3';
import { computed, ref, shallowRef } from 'vue';
import { updateConsent } from '@/lib/analytics';
import { writeConsentCookie } from '@/lib/consent';
import type { ConsentChoice } from '@/lib/consent';
import type { SitePageProps } from '@/types/site';

// CMP-COOKIE-01 state shared by the banner, the preferences dialog and the
// footer link. Module state is only written on the client (a click or
// onMounted), never while rendering, so it cannot leak between SSR requests.
// `undefined` = the visitor has not decided on this page yet, so the server's
// `ui.consent` (the cookie) applies.
const decided = shallowRef<ConsentChoice | undefined>(undefined);
const mounted = ref(false);
const preferencesOpen = ref(false);
const opener = shallowRef<HTMLElement | null>(null);

export function useConsent() {
    const page = usePage<SitePageProps>();
    const cookies = computed(() => page.props.ui.cookies);

    const consent = computed<ConsentChoice | null>(
        () => decided.value ?? page.props.ui.consent,
    );

    /** No consent wall: the banner is a bottom card that waits for a choice. */
    const bannerOpen = computed(
        () =>
            mounted.value &&
            cookies.value.enabled &&
            consent.value === null &&
            !preferencesOpen.value,
    );

    function save(
        choice: Pick<ConsentChoice, 'analytics' | 'marketing'>,
    ): void {
        const stored: ConsentChoice = {
            version: cookies.value.version,
            ...choice,
        };

        decided.value = stored;
        writeConsentCookie(stored);
        updateConsent(stored);
        preferencesOpen.value = false;
    }

    return {
        cookies,
        consent,
        bannerOpen,
        preferencesOpen,
        opener,
        /** Set by the public layout once it is on screen. */
        markMounted(): void {
            mounted.value = true;
        },
        acceptAll: () => save({ analytics: true, marketing: true }),
        rejectNonEssential: () => save({ analytics: false, marketing: false }),
        save,
        openPreferences(): void {
            opener.value =
                document.activeElement instanceof HTMLElement
                    ? document.activeElement
                    : null;
            preferencesOpen.value = true;
        },
    };
}
