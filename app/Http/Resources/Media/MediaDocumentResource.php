<?php

namespace App\Http\Resources\Media;

use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A PDF in the `MediaDocument` shape of resources/js/types/site.ts: `{url, bytes}`.
 *
 * @property MediaAsset $resource
 */
class MediaDocumentResource extends JsonResource
{
    /**
     * @return array{url: string, bytes: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'url' => (string) $this->resource->url(),
            'bytes' => (int) $this->resource->file()?->size,
        ];
    }
}
