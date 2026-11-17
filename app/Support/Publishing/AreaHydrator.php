<?php

namespace App\Support\Publishing;

use App\Actions\Seo\SaveSeoMeta;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\SeoMeta;
use App\Support\Locations\AreaAttributes;
use Illuminate\Support\Collection;

/**
 * An unsaved copy of an area as an editor payload would make it (CMS-PUB-002): the live row with the
 * payload's columns, and its city, hero, nearby areas and SEO set as loaded relations. Nothing is
 * written; mirrors `SaveArea` through the same `AreaAttributes` mapping.
 */
final class AreaHydrator
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(Location $live, array $payload): Location
    {
        $area = $live->replicate();
        $area->forceFill([...AreaAttributes::fromPayload($payload), 'id' => $live->id, 'created_at' => $live->created_at, 'updated_at' => $live->updated_at]);
        $area->exists = true;

        $seoValues = SaveSeoMeta::values($payload['seo'] ?? null);
        $assetIds = array_filter([$area->hero_asset_id, $seoValues['og_image_asset_id']]);
        /** @var Collection<int, MediaAsset> $assets */
        $assets = $assetIds === [] ? collect() : MediaAsset::query()->whereKey($assetIds)->with('media')->get()->keyBy('id');

        $seo = SaveSeoMeta::isDefault($seoValues) ? null : (new SeoMeta)->forceFill($seoValues);
        $seo?->setRelation('ogImage', $assets->get((int) ($seoValues['og_image_asset_id'] ?? 0)));

        $nearbyIds = array_values(array_unique(array_map(intval(...), $payload['nearby_ids'] ?? [])));
        $nearby = Location::query()->whereKey($nearbyIds)->get()
            ->sortBy(fn (Location $other): int|false => array_search($other->id, $nearbyIds, true))->values();

        $area->setRelation('parent', Location::withTrashed()->find($area->parent_id));
        $area->setRelation('hero', $assets->get((int) $area->hero_asset_id));
        $area->setRelation('nearby', $nearby);
        $area->setRelation('seoMeta', $seo);
        $area->syncOriginal();

        return $area;
    }
}
