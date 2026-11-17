<?php

namespace App\Http\Resources\Site;

use App\Http\Resources\Media\MediaImageResource;
use App\Models\Location;
use App\Queries\Site\AreaDirectory;
use App\Support\Demo\DemoCatalogFaqs;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PG-06: the stored part of `AreaDetail` (types/location.ts): identity, hero, facts, FAQ and the
 * nearby areas (SEO-ONP-005). The page adds what depends on the request (title, intro, segment,
 * breadcrumbs). Needs `parent`, `hero.media` and `nearby` loaded.
 *
 * @property Location $resource
 */
class LocationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $area = $this->resource;

        return [
            'slug' => $area->slug,
            'name' => $area->name,
            'city' => $area->parent->name ?? '',
            'url' => '/locations/'.$area->slug,
            'hero' => $area->hero === null ? null : MediaImageResource::make($area->hero)->resolve($request),
            'facts' => $area->facts ?? [],
            'faq' => DemoCatalogFaqs::area($area->slug),
            'nearby' => $this->nearby($area),
        ];
    }

    /**
     * Hand-picked published areas (≤ 6) as location links.
     *
     * @return list<array{name: string, city: string, count: int, url: string}>
     */
    private function nearby(Location $area): array
    {
        $links = [];

        foreach ($area->nearby->take(Location::NEARBY_MAX) as $nearby) {
            $row = AreaDirectory::find($nearby->slug);

            if ($row !== null) {
                $links[] = ['name' => $row['name'], 'city' => $row['city'], 'count' => $row['count'], 'url' => '/locations/'.$row['slug']];
            }
        }

        return $links;
    }
}
