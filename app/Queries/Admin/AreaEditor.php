<?php

namespace App\Queries\Admin;

use App\Enums\Segment;
use App\Models\Location;

/**
 * The area editor's form values (ADM-SCR-04) in the shape of `SaveAreaRequest`. The editor submits
 * the same shape back, and a pending revision (CMS-PUB-002) stores it, so nothing is translated twice.
 */
final class AreaEditor
{
    /**
     * @return array<string, mixed>
     */
    public static function form(?Location $area, ?int $cityId = null): array
    {
        $area?->loadMissing(['nearby', 'seoMeta']);

        return [
            'name' => $area->name ?? '',
            'slug' => $area->slug ?? '',
            'parent_id' => $area->parent_id ?? $cityId,
            'intro' => $area->intro ?? '',
            'segment_intros' => collect(Segment::cases())->mapWithKeys(fn (Segment $segment): array => [$segment->value => $area?->segment_intros[$segment->value] ?? ''])->all(),
            'facts' => $area->facts ?? [],
            'hero_asset_id' => $area?->hero_asset_id,
            'latitude' => $area?->latitude,
            'longitude' => $area?->longitude,
            'zoom' => $area->zoom ?? 14,
            'nearby_ids' => $area?->nearby->pluck('id')->all() ?? [],
            'seo' => ProjectEditor::seo($area?->seoMeta),
        ];
    }
}
