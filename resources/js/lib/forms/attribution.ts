// FR-LEAD-001: UTM parameters of the landing URL and the referrer host are
// captured into sessionStorage and sent with every lead as hidden fields.
// Browser-only: call from onMounted, never from setup/render (SEO-REN-08).

const KEY = 'gc:attribution';
const UTM_KEYS = [
    'utm_source',
    'utm_medium',
    'utm_campaign',
    'utm_term',
    'utm_content',
] as const;

export type Attribution = Partial<
    Record<(typeof UTM_KEYS)[number] | 'referrer_host', string>
>;

function read(): Attribution {
    try {
        return JSON.parse(
            window.sessionStorage.getItem(KEY) ?? '{}',
        ) as Attribution;
    } catch {
        return {};
    }
}

function write(value: Attribution): void {
    try {
        window.sessionStorage.setItem(KEY, JSON.stringify(value));
    } catch {
        // Private mode or blocked storage: forms still work without attribution.
    }
}

/** Keep the first landing's values; a URL with new UTM values replaces them. */
export function captureAttribution(): void {
    const stored = read();
    const params = new URLSearchParams(window.location.search);
    const next: Attribution = { ...stored };
    let changed = false;

    if (UTM_KEYS.some((key) => params.has(key))) {
        for (const key of UTM_KEYS) {
            const value = params.get(key)?.slice(0, 100);

            if (value) {
                next[key] = value;
            } else {
                delete next[key];
            }
        }

        changed = true;
    }

    if (stored.referrer_host === undefined && document.referrer) {
        try {
            const host = new URL(document.referrer).host;

            if (host && host !== window.location.host) {
                next.referrer_host = host.slice(0, 190);
                changed = true;
            }
        } catch {
            // An unparsable referrer is ignored.
        }
    }

    if (changed) {
        write(next);
    }
}

export function readAttribution(): Attribution {
    return read();
}
