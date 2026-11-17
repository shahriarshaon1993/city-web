<?php

namespace App\Http\Resources\Site;

use App\Http\Resources\Media\MediaImageResource;
use App\Models\Project;
use App\Support\Catalog\CatalogCopy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CMP-CARD-01: the `ProjectCard` of resources/js/types/site.ts. The only place a card is shaped,
 * so a hidden price can leave the server nowhere else (AC-PROJ-01): the price comes from
 * `Project::publicPriceFrom()`. Needs `location.parent` and `cover.media` loaded (ProjectCards does).
 *
 * @property Project $resource
 */
class ProjectCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $project = $this->resource;
        $price = $project->publicPriceFrom();
        $katha = $project->land_area_katha;

        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'url' => '/projects/'.$project->slug,
            'name' => $project->name,
            'segment' => $project->segment->value,
            'segment_label' => $project->segment->label(),
            'lifecycle' => $project->lifecycle->value,
            'lifecycle_label' => $project->lifecycle->label(),
            'booking_state' => $project->booking_state->value,
            'booking_label' => $project->booking_state->label(),
            'is_featured' => $project->is_featured,
            'location' => [
                'area' => $project->location->name,
                'city' => $project->location->parent->name ?? '',
            ],
            'cover' => $project->cover === null ? null : MediaImageResource::make($project->cover)->resolve($request),
            'size_min_sft' => $project->size_min_sft,
            'size_max_sft' => $project->size_max_sft,
            'beds_min' => $project->beds_min,
            'beds_max' => $project->beds_max,
            'baths_min' => $project->baths_min,
            'baths_max' => $project->baths_max,
            'land_katha' => $katha !== null && floor($katha) === $katha ? (int) $katha : $katha,
            'price_from_bdt' => $price,
            'price_note' => $price === null ? ($project->price_note ?: CatalogCopy::PRICE_ON_REQUEST) : null,
        ];
    }
}
