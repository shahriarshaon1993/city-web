<?php

namespace App\Http\Resources\Site;

use App\Enums\GalleryCategory;
use App\Enums\VideoType;
use App\Http\Resources\Media\MediaImageResource;
use App\Models\Amenity;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectNearbyPlace;
use App\Models\ProjectProgressUpdate;
use App\Models\ProjectUnitType;
use App\Support\Demo\DemoCatalogFaqs;
use App\Support\Format;
use App\Support\Media\AssetMap;
use App\Support\Search\ProjectFilters;
use App\Support\Seo\Breadcrumbs;
use Illuminate\Http\Request;

/**
 * PG-04: the `ProjectDetail` of resources/js/types/project.ts, on top of the card. Every price goes
 * through `Project::publicPriceFrom()` / `ProjectUnitType::publicPrice()`, so a hidden price never
 * reaches the page (AC-PROJ-01, DEC-54). Gated documents carry no URL (AC-PROJ-05). The enquiry
 * panel is added by `ProjectDetail`, outside the cache, because it reads Settings.
 *
 * Needs loaded: location.parent, amenities, unitTypes, progressUpdates.photos.media, nearbyPlaces,
 * documents; `similar` is the AC-PROJ-07 list of cards passed in.
 */
class ProjectDetailResource extends ProjectCardResource
{
    /**
     * @param  list<array<string, mixed>>  $similar  ready ProjectCard props
     */
    public function __construct(Project $resource, private readonly AssetMap $assets, private readonly array $similar)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $project = $this->resource;
        $card = parent::toArray($request);
        $gallery = $this->gallery($project);

        return [
            ...$card,
            // The cover is the first photo of the gallery (HERO-012).
            'cover' => $this->assets->image($project->cover_asset_id) ?? $gallery[0]['image'] ?? null,
            'tagline' => $project->tagline,
            'segment_url' => ProjectFilters::landingUrl('segment', $project->segment->value),
            'location' => [
                ...$card['location'],
                'url' => ProjectFilters::fromQuery(['area' => [$project->location->slug]], [$project->location->slug])->url(false),
            ],
            'handover_date' => $project->handover_date?->toDateString(),
            'gallery' => $gallery,
            'overview' => $project->overview,
            'design_narrative' => $project->design_narrative,
            'highlights' => $this->highlights($project),
            'facts' => [
                'address' => $project->address,
                'land_area_katha' => $project->land_area_katha,
                'storeys' => $project->storeys,
                'total_units' => $project->total_units,
                'units_per_floor' => $project->units_per_floor,
                'lifts' => $project->lifts,
                'car_parks' => $project->car_parks,
                'basements' => $project->basements,
                'road_width_ft' => $project->road_width_ft,
                'facing' => $project->facing,
                'orientation' => $project->orientation,
                'launch_date' => $project->launch_date?->toDateString(),
                'handover_date' => $project->handover_date?->toDateString(),
                'rajuk_approval_no' => $project->rajuk_approval_no,
            ],
            'custom_facts' => $project->custom_facts ?? [],
            'amenities' => $project->amenities->map(fn (Amenity $amenity): array => [
                'id' => $amenity->id,
                'slug' => $amenity->slug,
                'name' => $amenity->name,
                'icon' => $amenity->icon,
                'category' => $amenity->category->value,
                'category_label' => $amenity->category->label(),
            ])->all(),
            'unit_types' => $project->unitTypes->map(fn (ProjectUnitType $unit): array => $this->unit($project, $unit))->all(),
            'breadcrumbs' => Breadcrumbs::to($project->name, [['Projects', ProjectFilters::basePath()]]),
            'video' => $this->video($project),
            'virtual_tour_url' => $project->virtual_tour_url,
            'progress' => $project->progressUpdates->map(fn (ProjectProgressUpdate $update): array => $this->progress($update, $request))->all(),
            'coordinates' => $this->coordinates($project),
            'address' => $project->address,
            'nearby' => $project->nearbyPlaces->map(fn (ProjectNearbyPlace $place): array => [
                'id' => $place->id,
                'name' => $place->name,
                'category' => $place->category->value,
                'category_label' => $place->category->label(),
                'minutes' => $place->minutes,
                'mode' => $place->mode->value,
                'mode_label' => $place->mode->label(),
            ])->all(),
            'design_team' => $this->designTeam($project),
            'documents' => $project->documents->map(fn (ProjectDocument $document): array => $this->document($document))->all(),
            'faq' => DemoCatalogFaqs::project($project->segment->value, $project->id),
            'similar' => $this->similar,
        ];
    }

    /**
     * Gallery items in the editors' order; an item whose photo is gone is dropped.
     *
     * @return list<array<string, mixed>>
     */
    private function gallery(Project $project): array
    {
        $items = [];

        foreach ($project->gallery ?? [] as $item) {
            $image = $this->assets->image($item['asset_id'], $item['caption']);
            $category = GalleryCategory::tryFrom($item['category']) ?? GalleryCategory::Exterior;

            if ($image !== null) {
                $items[] = [
                    'id' => count($items) + 1,
                    'category' => $category->value,
                    'category_label' => $category->label(),
                    'image' => $image,
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<array{id: int, title: string, text: string, image: array<string, mixed>|null}>
     */
    private function highlights(Project $project): array
    {
        $items = [];

        foreach ($project->highlights ?? [] as $item) {
            $items[] = [
                'id' => count($items) + 1,
                'title' => $item['title'],
                'text' => $item['text'],
                'image' => $this->assets->image($item['asset_id']),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function unit(Project $project, ProjectUnitType $unit): array
    {
        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'size_sft' => $unit->size_sft,
            'size_sft_max' => $unit->size_sft_max,
            'bedrooms' => $unit->bedrooms,
            'bathrooms' => $unit->bathrooms,
            'balconies' => $unit->balconies,
            'facing' => $unit->facing,
            'floor_range' => $unit->floor_range,
            'floor_plan' => $this->assets->image($unit->floor_plan_asset_id),
            'plan_description' => $unit->plan_description,
            'price_bdt' => $unit->publicPrice($project),
            'availability' => $unit->availability->value,
            'availability_label' => $unit->availability->label(),
        ];
    }

    /**
     * CMP-VIDEO-01: a YouTube video (no duration is stored) or an uploaded MP4 with optional captions.
     *
     * @return array<string, mixed>|null
     */
    private function video(Project $project): ?array
    {
        $title = "{$project->name} project film";
        $poster = $this->assets->image($project->video_poster_asset_id);

        if ($project->video_type === VideoType::Youtube && preg_match('~(?:v=|youtu\.be/|embed/)([\w-]{11})~', (string) $project->video_url, $match) === 1) {
            return ['type' => 'youtube', 'title' => $title, 'poster' => $poster, 'duration_seconds' => null, 'youtube_id' => $match[1]];
        }

        $mp4 = $project->video_type === VideoType::Upload ? $this->assets->url($project->video_asset_id) : null;

        return $mp4 === null ? null : [
            'type' => 'upload',
            'title' => $title,
            'poster' => $poster,
            'duration_seconds' => null,
            'mp4' => $mp4,
            'webm' => null,
            'captions' => $this->assets->url($project->video_captions_asset_id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function progress(ProjectProgressUpdate $update, Request $request): array
    {
        $month = Format::month($update->reported_on);

        return [
            'id' => $update->id,
            'reported_on' => $update->reported_on->toDateString(),
            'percent' => $update->percent,
            'stage' => $update->stage->value,
            'stage_label' => $update->stage->label(),
            'note' => $update->note,
            'photos' => $update->photos->map(function ($photo) use ($request, $month): array {
                $image = MediaImageResource::make($photo)->resolve($request);

                return $image['caption'] === null ? [...$image, 'caption' => "Site progress, {$month}"] : $image;
            })->all(),
        ];
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    private function coordinates(Project $project): ?array
    {
        $lat = $project->latitude ?? $project->location->latitude;
        $lng = $project->longitude ?? $project->location->longitude;

        return $lat === null || $lng === null ? null : ['lat' => $lat, 'lng' => $lng];
    }

    /**
     * @return list<array{role: string, role_label: string, name: string}>
     */
    private function designTeam(Project $project): array
    {
        $team = [];

        foreach ([
            'architect' => ['Architect', $project->architect],
            'structural_engineer' => ['Structural engineer', $project->structural_engineer],
            'contractor' => ['Contractor', $project->contractor],
        ] as $role => [$label, $name]) {
            if ($name !== null && $name !== '') {
                $team[] = ['role' => $role, 'role_label' => $label, 'name' => $name];
            }
        }

        return $team;
    }

    /**
     * @return array<string, mixed>
     */
    private function document(ProjectDocument $document): array
    {
        $file = $this->assets->document($document->media_asset_id);

        return [
            'id' => $document->id,
            'title' => $document->title,
            'kind' => $document->kind->value,
            'kind_label' => $document->kind->label(),
            'is_gated' => $document->is_gated,
            'url' => $document->is_gated ? null : ($file['url'] ?? null),
            'bytes' => $file['bytes'] ?? null,
            'format' => 'PDF',
        ];
    }
}
