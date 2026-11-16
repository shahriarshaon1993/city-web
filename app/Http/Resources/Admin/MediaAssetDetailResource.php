<?php

namespace App\Http\Resources\Admin;

use App\Models\MediaUsage;
use App\Support\Media\UsesMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * The detail drawer (CMP-ADM-MEDIA): the library item plus every place that uses it, with links
 * (CMS-MED-007), and the `og` crop that follows the focal point.
 */
class MediaAssetDetailResource extends MediaAssetResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $asset = $this->resource;

        return [
            ...parent::toArray($request),
            'og_url' => $asset->ogUrl(),
            'usages' => $asset->usages()
                ->with('usable')
                ->orderBy('usable_type')
                ->orderBy('usable_id')
                ->orderBy('position')
                ->get()
                ->map(fn (MediaUsage $usage): array => self::usage($usage))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array{id: int, label: string, field: string, url: string|null}
     */
    private static function usage(MediaUsage $usage): array
    {
        $usable = $usage->usable;

        return [
            'id' => $usage->id,
            'label' => $usable instanceof UsesMedia
                ? $usable->mediaUsageLabel($usage->field)
                : Str::headline($usage->usable_type).' #'.$usage->usable_id,
            'field' => Str::headline($usage->field),
            'url' => $usable instanceof UsesMedia ? $usable->mediaUsageUrl($usage->field) : null,
        ];
    }
}
