<?php

namespace App\Queries\Site;

use App\Http\Resources\Site\LocationResource;
use App\Models\Location;
use App\Support\Catalog\CatalogCache;

/**
 * PG-06 data of one published area, cached 24 hours under the catalogue version (CMS-CACHE-003):
 * the `LocationResource` part of the `area` prop, the intro and area × segment intros (FR-LOC-004),
 * the map centre and the SEO overrides. Cities have no public page of their own.
 */
final class AreaPage
{
    /**
     * @return array{id: int, area: array<string, mixed>, intro: string, segment_intros: array<string, string>, centre: array{lat: float, lng: float, zoom: int}, seo: array<string, mixed>}|null
     */
    public static function find(string $slug): ?array
    {
        return CatalogCache::remember("areas:detail:{$slug}", CatalogCache::PAGE_TTL, function () use ($slug): ?array {
            $area = Location::query()->areas()->published()->where('slug', $slug)
                ->with(['parent', 'hero.media', 'nearby', 'seoMeta.ogImage.media'])->first();

            if ($area === null) {
                return null;
            }

            $seo = $area->seoMeta;

            return [
                'id' => $area->id,
                'area' => LocationResource::make($area)->resolve(),
                'intro' => (string) $area->intro,
                'segment_intros' => array_filter($area->segment_intros ?? [], fn (string $html): bool => trim(strip_tags($html)) !== ''),
                'centre' => ['lat' => $area->latitude ?? 23.8103, 'lng' => $area->longitude ?? 90.4125, 'zoom' => $area->zoom],
                'seo' => [
                    'meta_title' => $seo?->meta_title,
                    'meta_description' => $seo?->meta_description,
                    'robots' => $seo?->robots,
                    'canonical_url' => $seo?->canonical_url,
                    'og_image' => $seo?->ogImage?->ogUrl() ?? $area->hero?->ogUrl(),
                ],
            ];
        });
    }

    /**
     * SEO-TEC-003: the area that used to have this slug.
     */
    public static function movedTo(string $slug): ?string
    {
        $area = Location::findBySlugHistory($slug);

        return $area !== null && $area->isPublished() && ! $area->isCity() ? $area->slug : null;
    }
}
