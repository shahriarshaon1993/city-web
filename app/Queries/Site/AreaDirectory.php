<?php

namespace App\Queries\Site;

use App\Http\Resources\Media\MediaImageResource;
use App\Models\Location;
use App\Models\Project;
use App\Support\Catalog\CatalogCache;

/**
 * The published areas with their published project counts: the footer, the menus, the search
 * widget, the facets and the directory all read this one cached list (CMS-CACHE-002/003). Cities come
 * first by their sort order, areas by theirs, so Dhaka leads (HERO-040).
 *
 * @phpstan-type AreaRow array{id: int, slug: string, name: string, city: string, city_slug: string, count: int}
 */
final class AreaDirectory
{
    /**
     * @return list<AreaRow>
     */
    public static function all(): array
    {
        return CatalogCache::remember('areas:all', CatalogCache::LISTING_TTL, function (): array {
            $counts = Project::query()->published()
                ->selectRaw('location_id, count(*) as total')
                ->groupBy('location_id')
                ->pluck('total', 'location_id');

            $areas = Location::query()->areas()->published()->with('parent')->get()
                ->sortBy([
                    fn (Location $a, Location $b): int => [$a->parent->sort_order ?? 0, $a->parent_id] <=> [$b->parent->sort_order ?? 0, $b->parent_id],
                    fn (Location $a, Location $b): int => [$a->sort_order, $a->id] <=> [$b->sort_order, $b->id],
                ]);

            return array_values($areas->map(fn (Location $area): array => [
                'id' => $area->id,
                'slug' => $area->slug,
                'name' => $area->name,
                'city' => $area->parent->name ?? '',
                'city_slug' => $area->parent->slug ?? '',
                'count' => (int) ($counts[$area->id] ?? 0),
            ])->all());
        });
    }

    /**
     * The top areas by project count (CMS-NAV-003 `auto:locations`), ties in directory order.
     *
     * @return list<AreaRow>
     */
    public static function top(int $limit = 8): array
    {
        $areas = self::all();
        usort($areas, fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return array_slice($areas, 0, $limit);
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_column(self::all(), 'slug');
    }

    /**
     * @return AreaRow|null
     */
    public static function find(string $slug): ?array
    {
        return array_find(self::all(), fn (array $area): bool => $area['slug'] === $slug);
    }

    /**
     * Published projects per segment in one area (FR-LOC-004).
     *
     * @return array<string, int>
     */
    public static function segmentCounts(int $locationId): array
    {
        return CatalogCache::remember("areas:segments:{$locationId}", CatalogCache::LISTING_TTL, function () use ($locationId): array {
            return Project::query()->published()
                ->where('location_id', $locationId)
                ->selectRaw('segment, count(*) as total')
                ->groupBy('segment')
                ->pluck('total', 'segment')
                ->map(fn (mixed $total): int => (int) $total)
                ->all();
        });
    }

    /**
     * The hero photo of each published area (`area-tile` images of the directory and the
     * locations grid), by slug; null where the area has none.
     *
     * @return array<string, array<string, mixed>|null>
     */
    public static function images(): array
    {
        return CatalogCache::remember('areas:images', CatalogCache::LISTING_TTL, function (): array {
            return Location::query()->areas()->published()->with('hero.media')->get()
                ->mapWithKeys(fn (Location $area): array => [
                    $area->slug => $area->hero === null ? null : MediaImageResource::make($area->hero)->resolve(),
                ])->all();
        });
    }
}
