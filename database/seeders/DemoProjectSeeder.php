<?php

namespace Database\Seeders;

use App\Actions\Demo\ImportDemoMedia;
use App\Actions\Media\SyncMediaUsages;
use App\Actions\Projects\SyncProjectMediaUsages;
use App\Enums\PublishStatus;
use App\Enums\VideoType;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\Project;
use App\Support\Demo\DemoMedia;
use App\Support\Demo\DemoProjectDetail;
use App\Support\Demo\DemoProjects;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recreates the 27 demo projects of the approved frontend as real rows: facts, unit types (their size,
 * bed and price ranges are then computed by `RefreshProjectDerivedFields`), amenities, progress updates
 * with photos, nearby places and documents, with every photo imported into the media library
 * (DM-PROJ and friends). Placeholder content for the client's review; removed with the demo content
 * in 16.4. Run `DemoLocationSeeder` and `CatalogSeeder` first. Safe to run again (matched by slug).
 *
 * FAQs stay in the demo content until the FAQ module (16.4); the YouTube placeholder video is stored,
 * the optional uploaded demo film is not.
 *
 * @phpstan-import-type MediaImage from DemoMedia
 */
class DemoProjectSeeder extends Seeder
{
    /** Demo document file per DM-PDOC kind (`storage/app/demo-src/documents/{file}.pdf`). */
    private const array DOCUMENT_FILES = ['brochure' => 'brochure', 'price_list' => 'price-list', 'specification' => 'specification', 'other' => 'plot-layout'];

    public function __construct(
        private readonly ImportDemoMedia $importMedia,
        private readonly SyncMediaUsages $syncUsages,
        private readonly SyncProjectMediaUsages $syncProjectUsages,
    ) {}

    public function run(): void
    {
        foreach (DemoProjects::rows() as $row) {
            if (Project::withTrashed()->where('slug', $row['slug'])->exists()) {
                continue;
            }

            $detail = DemoProjectDetail::find($row['slug']);
            $location = Location::query()->where('slug', $row['area'])->first();

            if ($detail === null || $location === null) {
                continue;
            }

            DB::transaction(function () use ($row, $detail, $location): void {
                $gallery = [];

                foreach ($detail['gallery'] as $item) {
                    $id = $this->assetId($item['image']);

                    if ($id !== null) {
                        $gallery[] = ['asset_id' => $id, 'category' => $item['category'], 'caption' => $item['image']['caption'] ?? null];
                    }
                }

                $highlights = [];

                foreach ($detail['highlights'] as $item) {
                    $id = $this->assetId($item['image']);

                    if ($id !== null) {
                        $highlights[] = ['asset_id' => $id, 'title' => $item['title'], 'text' => $item['text']];
                    }
                }

                $team = array_column($detail['design_team'], 'name', 'role');
                $facts = $detail['facts'];
                $video = $detail['video'];
                $youtube = ($video['type'] ?? null) === 'youtube';
                $hasUnits = $detail['unit_types'] !== [];

                $project = Project::query()->create([
                    'name' => $row['name'],
                    'slug' => $row['slug'],
                    'tagline' => $row['tagline'],
                    'segment' => $row['segment'],
                    'lifecycle' => $row['lifecycle'],
                    'booking_state' => $row['booking_state'],
                    'location_id' => $location->id,
                    'address' => $facts['address'],
                    'latitude' => $detail['coordinates']['lat'],
                    'longitude' => $detail['coordinates']['lng'],
                    'land_area_katha' => $facts['land_area_katha'],
                    'storeys' => $facts['storeys'],
                    'total_units' => $facts['total_units'],
                    'car_parks' => $facts['car_parks'],
                    'units_per_floor' => $facts['units_per_floor'],
                    'lifts' => $facts['lifts'],
                    'basements' => $facts['basements'],
                    'road_width_ft' => $facts['road_width_ft'],
                    'facing' => $facts['facing'],
                    'orientation' => $facts['orientation'],
                    'launch_date' => $facts['launch_date'],
                    'handover_date' => $facts['handover_date'],
                    'rajuk_approval_no' => $facts['rajuk_approval_no'],
                    'architect' => $team['architect'] ?? null,
                    'structural_engineer' => $team['structural_engineer'] ?? null,
                    'contractor' => $team['contractor'] ?? null,
                    'custom_facts' => $detail['custom_facts'],
                    'overview' => $detail['overview'],
                    'design_narrative' => $detail['design_narrative'],
                    'highlights' => $highlights,
                    'gallery' => $gallery,
                    'cover_asset_id' => $gallery[0]['asset_id'] ?? null,
                    'video_type' => $youtube ? VideoType::Youtube : VideoType::None,
                    'video_url' => $youtube ? 'https://www.youtube.com/watch?v='.$video['youtube_id'] : null,
                    'video_poster_asset_id' => $youtube ? $this->assetId($video['poster']) : null,
                    'virtual_tour_url' => $detail['virtual_tour_url'],
                    'show_price' => $row['price_visible'],
                    // Projects with unit types derive the figure from them; land carries the editor's own.
                    'price_from_bdt' => $hasUnits ? null : $row['price'],
                    'price_from_manual' => ! $hasUnits && $row['price'] !== null,
                    'is_featured' => $row['featured'],
                    'featured_order' => $row['featured_order'],
                    'brochure_gated' => true,
                    'publish_status' => PublishStatus::Published,
                    'published_at' => $row['published_at'].' 09:00:00',
                ]);

                foreach ($detail['unit_types'] as $index => $unit) {
                    $project->unitTypes()->create([
                        'name' => $unit['name'],
                        'size_sft' => $unit['size_sft'],
                        'size_sft_max' => $unit['size_sft_max'],
                        'bedrooms' => $unit['bedrooms'],
                        'bathrooms' => $unit['bathrooms'],
                        'balconies' => $unit['balconies'],
                        'facing' => $unit['facing'],
                        'floor_range' => $unit['floor_range'],
                        'floor_plan_asset_id' => $this->assetId($unit['floor_plan']),
                        'plan_description' => $unit['plan_description'],
                        'price_bdt' => $unit['price_bdt'],
                        'show_price' => $unit['price_bdt'] !== null,
                        'availability' => $unit['availability'],
                        'sort_order' => $index,
                    ]);
                }

                $amenities = Amenity::query()->whereIn('slug', array_map(fn (array $amenity): string => Str::slug($amenity['name']), $detail['amenities']))->pluck('id', 'slug');

                foreach ($detail['amenities'] as $index => $amenity) {
                    if (isset($amenities[Str::slug($amenity['name'])])) {
                        $project->amenities()->attach($amenities[Str::slug($amenity['name'])], ['sort_order' => $index]);
                    }
                }

                foreach ($detail['progress'] as $update) {
                    $progress = $project->progressUpdates()->create([
                        'reported_on' => $update['reported_on'],
                        'percent' => $update['percent'],
                        'stage' => $update['stage'],
                        'note' => $update['note'],
                    ]);

                    $photos = array_values(array_filter(array_map($this->assetId(...), $update['photos'])));
                    $this->syncUsages->handle($progress, 'photos', $photos);
                }

                foreach ($detail['nearby'] as $index => $place) {
                    $project->nearbyPlaces()->create([
                        'name' => $place['name'],
                        'category' => $place['category'],
                        'minutes' => $place['minutes'],
                        'mode' => $place['mode'],
                        'sort_order' => $index,
                    ]);
                }

                foreach ($detail['documents'] as $index => $document) {
                    $asset = $this->importMedia->handle('documents/'.self::DOCUMENT_FILES[$document['kind']], $document['title']);

                    if ($asset !== null) {
                        $project->documents()->create([
                            'media_asset_id' => $asset->id,
                            'title' => $document['title'],
                            'kind' => $document['kind'],
                            'is_gated' => $document['is_gated'],
                            'sort_order' => $index,
                        ]);
                    }
                }

                $this->syncProjectUsages->handle($project->load('unitTypes'));
            });
        }
    }

    /**
     * The library item for a demo image, imported on first use.
     *
     * @param  MediaImage|null  $image
     */
    private function assetId(?array $image): ?int
    {
        $key = $image === null ? null : DemoMedia::keyOf($image);

        return $key === null ? null : $this->importMedia->handle($key)?->id;
    }
}
