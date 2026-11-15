<?php

namespace App\Http\Resources\Admin;

use App\Models\MediaAsset;
use App\Support\Format;
use App\Support\Media\MediaKind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A library item for the admin (library grid, picker, detail drawer — built in 11.2). The public
 * `MediaImage` shape is `MediaImageResource`; this one adds what only editors need.
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
            'conversions_status' => $asset->conversions_status,
            'uploaded_by' => $asset->uploader?->name,
            'created_at' => $asset->created_at?->toIso8601String(),
        ];
    }
}
