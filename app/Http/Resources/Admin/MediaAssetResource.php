<?php

namespace App\Http\Resources\Admin;

use App\Actions\Media\ReplaceMediaEverywhere;
use App\Models\MediaAsset;
use App\Support\Format;
use App\Support\Media\MediaKind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A library item for the admin (library grid and list, picker, detail drawer). The public
 * `MediaImage` shape is `MediaImageResource`; this one adds what only editors need, and what the
 * signed-in user may do with it (ARC-005, HELP-08).
 *
 * @property MediaAsset $resource
 */
class MediaAssetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $asset = $this->resource;
        $media = $asset->file();
        $actor = $request->user();
        $usages = $asset->getAttribute('usages_count');
        $mayDelete = $actor?->can('delete', $asset) ?? false;
        $deleteBlock = $mayDelete && $usages !== null && (int) $usages > 0 ? $asset->binUsageBlock($actor) : null;

        return [
            'id' => $asset->id,
            'kind' => $asset->kind->value,
            'kind_label' => $asset->kind->label(),
            'title' => $asset->title,
            'alt' => $asset->alt,
            'caption' => $asset->caption,
            'credit' => $asset->credit,
            'is_decorative' => $asset->is_decorative,
            // CMS-MED-004 / CMS-MED-009: an image needs alt text unless it is marked decorative.
            'missing_alt' => in_array($asset->kind, [MediaKind::Image, MediaKind::Svg], true) && ! $asset->is_decorative && trim((string) $asset->alt) === '',
            'focal' => ['x' => $asset->focal_x, 'y' => $asset->focal_y],
            'dominant_color' => $asset->dominant_color,
            'tags' => $asset->tags ?? [],
            'folder_id' => $asset->folder_id,
            'folder' => $asset->relationLoaded('folder') ? $asset->folder?->name : null,
            'width' => $asset->width,
            'height' => $asset->height,
            'duration_s' => $asset->duration_s,
            'mime' => $media?->mime_type,
            'bytes' => (int) $media?->size,
            'size_label' => $media === null ? null : Format::fileSize((int) $media->size),
            'file_name' => $media?->file_name,
            'original_name' => $media?->getCustomProperty('original_name'),
            'url' => $asset->url(),
            'thumb_url' => $asset->thumbUrl(),
            'preview_url' => $this->previewUrl(),
            'conversions_status' => $asset->conversions_status,
            'usages_count' => $usages === null ? null : (int) $usages,
            'replacement_kinds' => ReplaceMediaEverywhere::replacementKinds($asset->kind),
            'uploaded_by' => $asset->relationLoaded('uploader') ? $asset->uploader?->name : null,
            'created_at' => $asset->created_at?->toIso8601String(),
            'can' => [
                'edit' => $actor?->can('update', $asset) ?? false,
                'delete_visible' => $mayDelete,
                'delete' => $mayDelete && $deleteBlock === null,
            ],
            'blocked' => [
                'delete' => $deleteBlock,
            ],
        ];
    }

    /**
     * A ~1280 px WebP for the drawer preview and the focal point picker; the original for SVG and
     * while the conversions are pending.
     */
    private function previewUrl(): ?string
    {
        $asset = $this->resource;
        $media = $asset->file();

        if ($media === null || ! in_array($asset->kind, [MediaKind::Image, MediaKind::Svg], true)) {
            return null;
        }

        $webp = $asset->kind === MediaKind::Image ? ($asset->renditions()->webp ?? []) : [];

        if ($webp === []) {
            return $media->getUrl();
        }

        $pick = $webp[0];

        foreach ($webp as $source) {
            if ($source['width'] <= 1280) {
                $pick = $source;
            }
        }

        return $asset->diskUrl($media, $pick['path']);
    }
}
