<?php

namespace App\Http\Resources\Media;

use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The hero background video in the `MediaVideo` shape (HERO-020, `DemoMedia::video()`): MP4
 * required, WebM optional. Built from the two library items: `new MediaVideoResource(['mp4' => $a, 'webm' => $b])`.
 *
 * @property array{mp4: MediaAsset, webm?: MediaAsset|null} $resource
 */
class MediaVideoResource extends JsonResource
{
    /**
     * @return array{mp4: string, webm: string|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'mp4' => (string) $this->resource['mp4']->url(),
            'webm' => ($this->resource['webm'] ?? null)?->url(),
        ];
    }
}
