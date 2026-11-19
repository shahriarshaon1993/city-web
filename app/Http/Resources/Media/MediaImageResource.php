<?php

namespace App\Http\Resources\Media;

use App\Models\MediaAsset;
use App\Support\Media\MediaKind;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * An image or SVG library item in the `MediaImage` prop shape of resources/js/types/site.ts —
 * the shape `ResponsiveImage` and every page expect (DEC-31).
 *
 * - `sources.avif` is null when GD had no AVIF; `sources.jpeg` is a srcset only for hero-sized
 *   photos (more than one JPEG), otherwise null and `fallback` alone is the `<img src>`.
 * - An SVG, or an image whose conversions are not ready yet, is shaped like DEC-47: `webp: ''`,
 *   `fallback` = the original file, so the component renders a plain `<img>`.
 *
 * @property MediaAsset $resource
 *
 * @phpstan-type MediaImageShape array{
 *     alt: string,
 *     decorative: bool,
 *     caption: string|null,
 *     credit: string|null,
 *     width: int,
 *     height: int,
 *     focal: array{x: int, y: int},
 *     dominant_color: string|null,
 *     sources: array{avif: string|null, webp: string, jpeg: string|null},
 *     fallback: string
 * }
 */
class MediaImageResource extends JsonResource
{
    /**
     * @return MediaImageShape
     */
    public function toArray(Request $request): array
    {
        $asset = $this->resource;
        $media = $asset->file();
        $renditions = $asset->kind === MediaKind::Image ? $asset->renditions() : null;
        $disk = $media === null ? null : Storage::disk($media->disk);

        /** @param list<array{path: string, width: int}> $items */
        $srcset = function (array $items) use ($disk): ?string {
            if ($items === [] || $disk === null) {
                return null;
            }

            return implode(', ', array_map(
                fn (array $item): string => $disk->url($item['path']).' '.$item['width'].'w',
                $items,
            ));
        };

        return [
            'alt' => $asset->is_decorative ? '' : (string) $asset->alt,
            'decorative' => $asset->is_decorative,
            'caption' => $asset->caption,
            'credit' => $asset->credit,
            'width' => (int) $asset->width,
            'height' => (int) $asset->height,
            'focal' => ['x' => $asset->focal_x, 'y' => $asset->focal_y],
            'dominant_color' => $renditions === null ? null : $asset->dominant_color,
            'sources' => [
                'avif' => $renditions === null ? null : $srcset($renditions->avif),
                'webp' => $renditions === null ? '' : ($srcset($renditions->webp) ?? ''),
                'jpeg' => $renditions !== null && count($renditions->jpeg) > 1 ? $srcset($renditions->jpeg) : null,
            ],
            'fallback' => $renditions !== null && $disk !== null ? $disk->url($renditions->fallback) : (string) $asset->url(),
        ];
    }
}
