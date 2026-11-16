<?php

namespace App\Queries\Admin;

use App\Enums\Availability;
use App\Enums\BookingState;
use App\Enums\DocumentKind;
use App\Enums\GalleryCategory;
use App\Enums\Lifecycle;
use App\Enums\NearbyCategory;
use App\Enums\ProgressStage;
use App\Enums\Segment;
use App\Enums\TravelMode;
use App\Http\Resources\Admin\MediaAssetResource;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\ProjectProgressUpdate;
use App\Models\User;
use App\Support\Auth\SystemRole;
use Illuminate\Http\Request;

/**
 * What the project editor (ADM-SCR-01) needs besides the record: the form values in the shape of
 * `SaveProjectRequest`, the library items they point at (for previews), and the option lists.
 * The editor submits the same shape back, so nothing is translated twice.
 */
final class ProjectEditor
{
    /**
     * @return array<string, mixed>
     */
    public static function form(?Project $project): array
    {
        if ($project === null) {
            return self::blank();
        }

        $project->loadMissing(['unitTypes', 'amenities', 'progressUpdates.photos', 'nearbyPlaces', 'documents', 'similar', 'seoMeta']);
        $seo = $project->seoMeta;

        return [
            'name' => $project->name,
            'slug' => $project->slug,
            'tagline' => $project->tagline ?? '',
            'segment' => $project->segment->value,
            'lifecycle' => $project->lifecycle->value,
            'booking_state' => $project->booking_state->value,
            'location_id' => $project->location_id,
            'is_featured' => $project->is_featured,

            'address' => $project->address ?? '',
            'latitude' => $project->latitude,
            'longitude' => $project->longitude,
            'land_area_katha' => $project->land_area_katha,
            'storeys' => $project->storeys ?? '',
            'total_units' => $project->total_units,
            'units_per_floor' => $project->units_per_floor,
            'lifts' => $project->lifts,
            'car_parks' => $project->car_parks,
            'basements' => $project->basements,
            'road_width_ft' => $project->road_width_ft,
            'facing' => $project->facing ?? '',
            'orientation' => $project->orientation ?? '',
            'launch_date' => $project->launch_date?->format('Y-m') ?? '',
            'handover_date' => $project->handover_date?->format('Y-m') ?? '',
            'rajuk_approval_no' => $project->rajuk_approval_no ?? '',
            'architect' => $project->architect ?? '',
            'structural_engineer' => $project->structural_engineer ?? '',
            'contractor' => $project->contractor ?? '',
            'custom_facts' => $project->custom_facts ?? [],

            'overview' => $project->overview ?? '',
            'design_narrative' => $project->design_narrative ?? '',
            'highlights' => $project->highlights ?? [],

            'cover_asset_id' => $project->cover_asset_id,
            'gallery' => array_map(fn (array $item): array => [...$item, 'caption' => $item['caption'] ?? ''], $project->gallery ?? []),
            'video_type' => $project->video_type->value,
            'video_url' => $project->video_url ?? '',
            'video_asset_id' => $project->video_asset_id,
            'video_poster_asset_id' => $project->video_poster_asset_id,
            'video_captions_asset_id' => $project->video_captions_asset_id,
            'virtual_tour_url' => $project->virtual_tour_url ?? '',

            'unit_types' => $project->unitTypes->map(fn ($unit): array => [
                'id' => $unit->id,
                'name' => $unit->name,
                'size_sft' => $unit->size_sft,
                'size_sft_max' => $unit->size_sft_max,
                'bedrooms' => $unit->bedrooms,
                'bathrooms' => $unit->bathrooms,
                'balconies' => $unit->balconies,
                'facing' => $unit->facing ?? '',
                'floor_range' => $unit->floor_range ?? '',
                'floor_plan_asset_id' => $unit->floor_plan_asset_id,
                'plan_description' => $unit->plan_description ?? '',
                'plan_3d_asset_id' => $unit->plan_3d_asset_id,
                'price_bdt' => $unit->price_bdt,
                'show_price' => $unit->show_price,
                'availability' => $unit->availability->value,
            ])->all(),

            'amenity_ids' => $project->amenities->pluck('id')->all(),

            'progress_updates' => $project->progressUpdates->map(fn (ProjectProgressUpdate $update): array => [
                'id' => $update->id,
                'reported_on' => $update->reported_on->format('Y-m'),
                'percent' => $update->percent,
                'stage' => $update->stage->value,
                'note' => $update->note ?? '',
                'photo_ids' => $update->photos->pluck('id')->all(),
            ])->all(),

            'nearby_places' => $project->nearbyPlaces->map(fn ($place): array => [
                'name' => $place->name,
                'category' => $place->category->value,
                'minutes' => $place->minutes,
                'mode' => $place->mode->value,
            ])->all(),

            'documents' => $project->documents->map(fn ($document): array => [
                'asset_id' => $document->media_asset_id,
                'title' => $document->title,
                'kind' => $document->kind->value,
                'is_gated' => $document->is_gated,
            ])->all(),
            'brochure_gated' => $project->brochure_gated,

            'show_price' => $project->show_price,
            'price_from_manual' => $project->price_from_manual,
            'price_from_bdt' => $project->price_from_bdt,
            'price_note' => $project->price_note ?? '',

            'default_assignee_id' => $project->default_assignee_id,
            'whatsapp_override' => $project->whatsapp_override ?? '',
            'similar_ids' => $project->similar->pluck('id')->all(),

            'seo' => self::seo($seo),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function blank(): array
    {
        return [
            'name' => '', 'slug' => '', 'tagline' => '',
            'segment' => Segment::Residential->value, 'lifecycle' => Lifecycle::Upcoming->value, 'booking_state' => BookingState::None->value,
            'location_id' => null, 'is_featured' => false,
            'address' => '', 'latitude' => null, 'longitude' => null, 'land_area_katha' => null, 'storeys' => '',
            'total_units' => null, 'units_per_floor' => null, 'lifts' => null, 'car_parks' => null, 'basements' => null, 'road_width_ft' => null,
            'facing' => '', 'orientation' => '', 'launch_date' => '', 'handover_date' => '',
            'rajuk_approval_no' => '', 'architect' => '', 'structural_engineer' => '', 'contractor' => '', 'custom_facts' => [],
            'overview' => '', 'design_narrative' => '', 'highlights' => [],
            'cover_asset_id' => null, 'gallery' => [], 'video_type' => 'none', 'video_url' => '',
            'video_asset_id' => null, 'video_poster_asset_id' => null, 'video_captions_asset_id' => null, 'virtual_tour_url' => '',
            'unit_types' => [], 'amenity_ids' => [], 'progress_updates' => [], 'nearby_places' => [], 'documents' => [], 'brochure_gated' => true,
            'show_price' => false, 'price_from_manual' => false, 'price_from_bdt' => null, 'price_note' => '',
            'default_assignee_id' => null, 'whatsapp_override' => '', 'similar_ids' => [],
            'seo' => self::seo(null),
        ];
    }

    /**
     * The library items the form points at, by id, in the admin media shape.
     *
     * @param  array<string, mixed>  $form
     * @return array<int, array<string, mixed>>
     */
    public static function assets(Request $request, array $form): array
    {
        $ids = array_filter([
            $form['cover_asset_id'], $form['video_asset_id'], $form['video_poster_asset_id'], $form['video_captions_asset_id'],
            $form['seo']['og_image_asset_id'],
            ...array_column($form['gallery'], 'asset_id'),
            ...array_column($form['highlights'], 'asset_id'),
            ...array_column($form['unit_types'], 'floor_plan_asset_id'),
            ...array_column($form['unit_types'], 'plan_3d_asset_id'),
            ...array_column($form['documents'], 'asset_id'),
            ...array_merge([], ...array_column($form['progress_updates'], 'photo_ids')),
        ]);

        if ($ids === []) {
            return [];
        }

        return MediaAsset::query()->whereKey(array_unique($ids))->with('media')->get()
            ->mapWithKeys(fn (MediaAsset $asset): array => [$asset->id => (new MediaAssetResource($asset))->resolve($request)])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function options(?Project $project): array
    {
        /** @param  class-string<Segment|Lifecycle|BookingState|Availability|GalleryCategory|ProgressStage|NearbyCategory|TravelMode|DocumentKind>  $enum */
        $labels = fn (string $enum): array => array_map(
            fn ($case): array => ['value' => $case->value, 'label' => $case->label() ?? 'None'],
            $enum::cases(),
        );

        return [
            'segments' => $labels(Segment::class),
            'lifecycles' => $labels(Lifecycle::class),
            'bookings' => $labels(BookingState::class),
            'availabilities' => $labels(Availability::class),
            'gallery_categories' => $labels(GalleryCategory::class),
            'progress_stages' => $labels(ProgressStage::class),
            'nearby_categories' => $labels(NearbyCategory::class),
            'travel_modes' => $labels(TravelMode::class),
            'document_kinds' => $labels(DocumentKind::class),
            'areas' => Location::query()->areas()->with('parent')->orderBy('sort_order')->orderBy('name')->get()
                ->sortBy(fn (Location $area): array => [$area->parent->sort_order ?? 0, $area->sort_order])
                ->map(fn (Location $area): array => ['value' => $area->id, 'label' => $area->name, 'group' => $area->parent->name ?? ''])
                ->values()->all(),
            'amenities' => Amenity::query()->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (Amenity $amenity): array => ['id' => $amenity->id, 'name' => $amenity->name, 'icon' => $amenity->icon, 'category' => $amenity->category->value, 'category_label' => $amenity->category->label()])
                ->all(),
            'similar' => Project::query()->when($project, fn ($query) => $query->whereKeyNot($project->id))->with('location')->orderBy('name')->get(['id', 'name', 'location_id'])
                ->map(fn (Project $other): array => ['value' => $other->id, 'label' => $other->name, 'group' => $other->location->name ?? ''])->all(),
            'assignees' => User::query()->role([SystemRole::SalesManager->value, SystemRole::SalesExecutive->value])->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user): array => ['value' => $user->id, 'label' => $user->name])->all(),
            'limits' => [
                'unit_types' => 20, 'gallery' => Project::GALLERY_MAX, 'highlights' => Project::HIGHLIGHTS_MAX, 'custom_facts' => Project::CUSTOM_FACTS_MAX,
                'nearby' => 10, 'documents' => 5, 'photos' => 12, 'similar' => Project::SIMILAR_MAX,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function seo(mixed $seo): array
    {
        return [
            'meta_title' => $seo->meta_title ?? '',
            'meta_description' => $seo->meta_description ?? '',
            'canonical_url' => $seo->canonical_url ?? '',
            'robots' => $seo->robots ?? 'index,follow',
            'og_title' => $seo->og_title ?? '',
            'og_description' => $seo->og_description ?? '',
            'og_image_asset_id' => $seo?->og_image_asset_id,
            'twitter_card' => $seo->twitter_card ?? 'summary_large_image',
            'focus_keyword' => $seo->focus_keyword ?? '',
            'schema_enabled' => $seo->schema_enabled ?? true,
        ];
    }
}
