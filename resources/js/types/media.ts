// Media library (ADM-SCR-13, CMS-MED-*). Shapes of App\Http\Resources\Admin\MediaAsset(Detail)Resource
// and App\Support\Media\{FolderTree, MediaLimits}.

export type MediaKind = 'image' | 'svg' | 'video' | 'document';

export type MediaAsset = {
    id: number;
    kind: MediaKind;
    kind_label: string;
    title: string;
    alt: string | null;
    caption: string | null;
    credit: string | null;
    is_decorative: boolean;
    missing_alt: boolean;
    focal: { x: number; y: number };
    dominant_color: string | null;
    tags: string[];
    folder_id: number | null;
    folder: string | null;
    width: number | null;
    height: number | null;
    duration_s: number | null;
    mime: string | null;
    bytes: number;
    size_label: string | null;
    file_name: string | null;
    original_name: string | null;
    url: string | null;
    thumb_url: string | null;
    preview_url: string | null;
    conversions_status: 'pending' | 'ready' | 'failed';
    usages_count: number | null;
    /** Kinds that may take its place in "Replace everywhere". */
    replacement_kinds: MediaKind[];
    uploaded_by: string | null;
    created_at: string | null;
    can: { edit: boolean; delete_visible: boolean; delete: boolean };
    blocked: { delete: string | null };
};

export type MediaUsageItem = {
    id: number;
    label: string;
    field: string;
    url: string | null;
};

export type MediaAssetDetail = MediaAsset & {
    og_url: string | null;
    usages: MediaUsageItem[];
};

export type MediaFolderNode = {
    id: number;
    parent_id: number | null;
    name: string;
    /** "Projects › Orchid › Interiors" */
    path: string;
    /** 1 = top level; folders go 3 deep (CMS-MED-002). */
    depth: number;
    count: number;
    delete_block: string | null;
    children: MediaFolderNode[];
};

export type MediaFolderTree = {
    all: number;
    unfiled: number;
    folders: MediaFolderNode[];
};

/** `filter[folder]`: a folder id, `unfiled`, or '' for all files. */
export type MediaFolderKey = string;

export type MediaLimits = {
    max_bytes: Record<MediaKind, number>;
    max_pixels: number;
    mime_types: string[];
    hero: {
        image_min: { width: number; height: number };
        mp4_bytes: number;
        webm_bytes: number;
        video_max: {
            width: number;
            height: number;
            seconds: number;
            fps: number;
        };
    };
};
