import { store } from '@/routes/admin/media';
import type { MediaAsset, MediaKind, MediaLimits } from '@/types';

// CMS-MED-003 / VAL-03: the dropzone refuses wrong files before uploading (type, size, pixels),
// with the same limits and wording as the server's UploadInspector, then sends each file on its
// own request so every file has its own progress bar. The server stays the truth: its `file`
// error is shown when it refuses something the browser could not check (e.g. a renamed file).

/** Extension → MIME the server accepts (MediaLimits::TYPES). */
const EXTENSIONS: Record<string, string> = {
    jpg: 'image/jpeg',
    jpeg: 'image/jpeg',
    png: 'image/png',
    webp: 'image/webp',
    avif: 'image/avif',
    svg: 'image/svg+xml',
    mp4: 'video/mp4',
    webm: 'video/webm',
    pdf: 'application/pdf',
};

export const ACCEPT = Object.keys(EXTENSIONS)
    .map((extension) => `.${extension}`)
    .join(',');

const KIND_NAMES: Record<MediaKind, string> = {
    image: 'Images',
    svg: 'SVGs',
    video: 'Videos',
    document: 'PDFs',
};

export function formatBytes(bytes: number): string {
    if (bytes >= 1024 * 1024) {
        const mb = bytes / (1024 * 1024);

        return `${Number.isInteger(mb) ? mb : mb.toFixed(1)} MB`;
    }

    return `${Math.ceil(bytes / 1024)} KB`;
}

export function kindOfMime(mime: string): MediaKind {
    if (mime === 'image/svg+xml') {
        return 'svg';
    }

    if (mime.startsWith('image/')) {
        return 'image';
    }

    return mime.startsWith('video/') ? 'video' : 'document';
}

/** The MIME the server would expect from the name; null = not an allowed type. */
export function mimeOfFile(file: File): string | null {
    const extension = file.name.split('.').pop()?.toLowerCase() ?? '';

    return EXTENSIONS[extension] ?? null;
}

/** "Up to 12 MB each · JPEG, PNG, WebP, AVIF, SVG, MP4, WebM, PDF" for the dropzone (VAL-03). */
export function limitsSummary(
    limits: MediaLimits,
    kinds?: MediaKind[],
): string {
    const shown = (kinds ?? (Object.keys(KIND_NAMES) as MediaKind[])).map(
        (kind) =>
            `${KIND_NAMES[kind]} up to ${formatBytes(limits.max_bytes[kind])}`,
    );

    return shown.join(' · ');
}

function readPixels(
    file: File,
): Promise<{ width: number; height: number } | null> {
    return new Promise((resolve) => {
        const url = URL.createObjectURL(file);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve({ width: image.naturalWidth, height: image.naturalHeight });
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            // The browser cannot decode it (e.g. AVIF on an old browser): let the server decide.
            resolve(null);
        };
        image.src = url;
    });
}

/**
 * Why the file would be refused, or null. `kinds` limits what the field accepts (picker presets).
 */
export async function checkFile(
    file: File,
    limits: MediaLimits,
    kinds?: MediaKind[],
): Promise<string | null> {
    const mime = mimeOfFile(file);

    if (mime === null || !limits.mime_types.includes(mime)) {
        return 'This type of file is not accepted. Use JPEG, PNG, WebP, AVIF, SVG, MP4, WebM or PDF.';
    }

    const kind = kindOfMime(mime);

    if (kinds && !kinds.includes(kind)) {
        return `This field takes ${kinds.map((item) => KIND_NAMES[item].toLowerCase()).join(' or ')} only.`;
    }

    const max = limits.max_bytes[kind];

    if (file.size > max) {
        return `The file is ${formatBytes(file.size)}; ${KIND_NAMES[kind].toLowerCase()} can be up to ${formatBytes(max)}.`;
    }

    if (kind === 'image') {
        const pixels = await readPixels(file);

        if (pixels && pixels.width * pixels.height > limits.max_pixels) {
            const megapixels = Math.round(
                (pixels.width * pixels.height) / 1_000_000,
            );

            return `The image is ${pixels.width} × ${pixels.height} (${megapixels} megapixels); the limit is ${limits.max_pixels / 1_000_000} megapixels. Resize it first.`;
        }
    }

    return null;
}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export type UploadResult =
    | { ok: true; asset: MediaAsset }
    | { ok: false; error: string };

/** One file, one request, with upload progress (0–100). */
export function uploadFile(
    file: File,
    options: {
        folderId?: number | null;
        onProgress?: (percent: number) => void;
        signal?: AbortSignal;
    } = {},
): Promise<UploadResult> {
    return new Promise((resolve) => {
        const request = new XMLHttpRequest();
        const body = new FormData();

        body.append('file', file);

        if (options.folderId) {
            body.append('folder_id', String(options.folderId));
        }

        request.open('POST', store.url());
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        request.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
        request.responseType = 'json';

        request.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                options.onProgress?.(
                    Math.round((event.loaded / event.total) * 100),
                );
            }
        };

        request.onload = () => {
            const data = request.response as {
                data?: MediaAsset;
                errors?: Record<string, string[]>;
                message?: string;
            } | null;

            if (request.status === 201 && data?.data) {
                resolve({ ok: true, asset: data.data });

                return;
            }

            const error =
                data?.errors?.file?.[0] ??
                Object.values(data?.errors ?? {})[0]?.[0] ??
                (request.status === 413
                    ? 'The file is larger than the server accepts.'
                    : request.status === 419
                      ? 'Your session expired. Sign in again in a new tab, then retry.'
                      : request.status === 403
                        ? 'You are not allowed to upload files.'
                        : 'The upload failed. Try again.');

            resolve({ ok: false, error });
        };

        request.onerror = () =>
            resolve({
                ok: false,
                error: 'The connection dropped. Check your network and try again.',
            });
        request.onabort = () =>
            resolve({ ok: false, error: 'Upload cancelled.' });

        options.signal?.addEventListener('abort', () => request.abort());

        request.send(body);
    });
}
