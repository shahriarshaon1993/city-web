import { reactive } from 'vue';
import {
    checkFile,
    kindOfMime,
    mimeOfFile,
    uploadFile,
} from '@/lib/media-upload';
import type { MediaAsset, MediaKind, MediaLimits } from '@/types';

// CMP-ADM-MEDIA upload progress list: one shared queue, so the dock (library) and the picker's
// "Upload new" tab show the same files. Files are checked first (VAL-03), then sent two at a time.

export type UploadItem = {
    key: number;
    name: string;
    size: number;
    status:
        | 'checking'
        | 'refused'
        | 'waiting'
        | 'uploading'
        | 'done'
        | 'failed';
    progress: number;
    error: string | null;
    asset: MediaAsset | null;
};

export type UploadBatchResult = {
    assets: MediaAsset[];
    /** Refused before or by the server. */
    failed: number;
    /** Uploaded raster images, whose conversions now run in the background. */
    images: number;
};

const CONCURRENCY = 2;

const items = reactive<UploadItem[]>([]);
let nextKey = 1;

function item(key: number): UploadItem | undefined {
    return items.find((entry) => entry.key === key);
}

async function add(
    files: File[] | FileList,
    options: {
        limits: MediaLimits;
        folderId?: number | null;
        kinds?: MediaKind[];
    },
): Promise<UploadBatchResult> {
    const batch = Array.from(files).map((file) => {
        const entry: UploadItem = {
            key: nextKey++,
            name: file.name,
            size: file.size,
            status: 'checking',
            progress: 0,
            error: null,
            asset: null,
        };

        items.unshift(entry);

        return { file, key: entry.key };
    });

    // VAL-03: refuse wrong files before anything is sent.
    const accepted: typeof batch = [];

    for (const job of batch) {
        const error = await checkFile(job.file, options.limits, options.kinds);
        const entry = item(job.key);

        if (!entry) {
            continue;
        }

        if (error) {
            Object.assign(entry, { status: 'refused', error });
        } else {
            entry.status = 'waiting';
            accepted.push(job);
        }
    }

    const assets: MediaAsset[] = [];
    let images = 0;
    let index = 0;

    async function worker(): Promise<void> {
        while (index < accepted.length) {
            const job = accepted[index++];
            const entry = item(job.key);

            if (!entry) {
                continue;
            }

            entry.status = 'uploading';

            const result = await uploadFile(job.file, {
                folderId: options.folderId,
                onProgress: (percent) => {
                    entry.progress = percent;
                },
            });

            if (result.ok) {
                Object.assign(entry, {
                    status: 'done',
                    progress: 100,
                    asset: result.asset,
                });
                assets.push(result.asset);

                if (kindOfMime(mimeOfFile(job.file) ?? '') === 'image') {
                    images++;
                }
            } else {
                Object.assign(entry, { status: 'failed', error: result.error });
            }
        }
    }

    await Promise.all(Array.from({ length: CONCURRENCY }, () => worker()));

    return { assets, failed: batch.length - assets.length, images };
}

function dismiss(key: number): void {
    const at = items.findIndex((entry) => entry.key === key);

    if (at !== -1) {
        items.splice(at, 1);
    }
}

/** Removes everything that has finished (done, refused or failed). */
function clearFinished(): void {
    for (let at = items.length - 1; at >= 0; at--) {
        if (['done', 'refused', 'failed'].includes(items[at].status)) {
            items.splice(at, 1);
        }
    }
}

export function useMediaUploads() {
    return { items, add, dismiss, clearFinished };
}
