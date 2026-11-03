// CMP-COOKIE-01 / NFR-PRIV-01: the visitor's cookie choice lives in one
// first-party cookie, `gc_consent`, for 12 months. The browser writes it, the
// server reads it (App\Support\Consent\ConsentCookie) so a returning visitor
// never sees the banner again. Browser-only: call from handlers and onMounted.

export const CONSENT_COOKIE = 'gc_consent';

export type ConsentChoice = {
    /** Settings → Legal & cookies `consent_version`; a bump re-prompts. */
    version: number;
    analytics: boolean;
    marketing: boolean;
};

const MAX_AGE = 60 * 60 * 24 * 365;

export function writeConsentCookie(choice: ConsentChoice): void {
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';

    document.cookie = `${CONSENT_COOKIE}=${encodeURIComponent(JSON.stringify(choice))}; Max-Age=${MAX_AGE}; Path=/; SameSite=Lax${secure}`;
}
