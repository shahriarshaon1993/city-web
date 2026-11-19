<?php

namespace Database\Seeders;

use App\Actions\Locations\SyncLocationMediaUsages;
use App\Enums\LocationType;
use App\Enums\PublishStatus;
use App\Models\Location;
use Database\Seeders\Data\DemoLocations;
use Database\Seeders\Data\DemoProjects;
use Database\Seeders\Support\ImportSeedMedia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Recreates the demo cities and areas of the approved frontend as real `locations` rows, with their
 * hero photos imported into the media library (DM-LOC, FR-LOC-001/002). Placeholder content for the
 * client's review; removed with the demo content in 16.4. Safe to run again (rows are matched by slug).
 */
class DemoLocationSeeder extends Seeder
{
    public function run(ImportSeedMedia $importMedia, SyncLocationMediaUsages $syncUsages): void
    {
        $published = ['publish_status' => PublishStatus::Published, 'published_at' => '2025-01-01 00:00:00'];
        $cities = [];

        foreach (DemoProjects::areas() as $index => $area) {
            $slug = Str::slug($area['city']);

            $cities[$slug] ??= Location::query()->firstOrCreate(
                ['slug' => $slug],
                [...$published, 'type' => LocationType::City, 'name' => $area['city'], 'zoom' => 11, 'sort_order' => count($cities)],
            );

            $detail = DemoLocations::detail($area['slug']);
            $centre = DemoLocations::centre($area['slug']);

            $location = Location::query()->firstOrNew(['slug' => $area['slug']]);

            if ($location->exists) {
                continue;
            }

            $location->fill([
                ...$published,
                'type' => LocationType::Area,
                'parent_id' => $cities[$slug]->id,
                'name' => $area['name'],
                'intro' => DemoLocations::introHtml($area['slug']),
                'facts' => array_map(fn (array $fact): array => ['label' => $fact[0], 'value' => $fact[1]], $detail['facts'] ?? []),
                'segment_intros' => $detail['segments'] ?? [],
                'hero_asset_id' => $importMedia->handle($detail['image'] ?? 'locations/locations-01')?->id,
                'latitude' => $centre['lat'],
                'longitude' => $centre['lng'],
                'zoom' => $centre['zoom'],
                'sort_order' => $index,
            ])->save();

            $syncUsages->handle($location);
        }

        // Nearby areas point at rows that all exist now.
        foreach (DemoProjects::areas() as $area) {
            $location = Location::query()->where('slug', $area['slug'])->first();
            $nearby = DemoLocations::detail($area['slug'])['nearby'] ?? [];

            if ($location === null || $location->nearby()->exists()) {
                continue;
            }

            $ids = Location::query()->whereIn('slug', $nearby)->pluck('id', 'slug');
            $sync = [];

            foreach ($nearby as $position => $slug) {
                if (isset($ids[$slug])) {
                    $sync[$ids[$slug]] = ['sort_order' => $position];
                }
            }

            $location->nearby()->sync($sync);
        }
    }
}
