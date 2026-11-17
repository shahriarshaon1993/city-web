// A small JSON request helper for admin calls that are not Inertia visits (autosave, copying a
// preview link, loading revisions). Sends the Laravel XSRF cookie like Inertia's own client does.
export class HttpError extends Error {
    constructor(
        public readonly status: number,
        public readonly body: unknown,
    ) {
        super(`Request failed with status ${status}`);
    }
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : '';
}

/** Sends `body` as JSON and returns the parsed JSON answer; a non-2xx answer throws an `HttpError`. */
export async function sendJson<T>(
    method: 'GET' | 'POST' | 'PATCH' | 'PUT' | 'DELETE',
    url: string,
    body?: unknown,
): Promise<T> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
            ...(body === undefined
                ? {}
                : { 'Content-Type': 'application/json' }),
        },
        ...(body === undefined ? {} : { body: JSON.stringify(body) }),
    });

    const text = await response.text();
    const parsed = text === '' ? null : (JSON.parse(text) as unknown);

    if (!response.ok) {
        throw new HttpError(response.status, parsed);
    }

    return parsed as T;
}
