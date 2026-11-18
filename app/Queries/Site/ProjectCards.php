<?php

namespace App\Queries\Site;

use App\Enums\Lifecycle;
use App\Enums\Segment;
use App\Http\Resources\Site\ProjectCardResource;
use App\Models\Project;
use App\Support\Catalog\CatalogCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Published projects as `ProjectCard` props (ARC-002): the featured, ongoing and newest rows of the
 * home page, menus, error pages and shortlist all read through here so a card is always shaped by
 * `ProjectCardResource`. Lists are cached 10 minutes under the catalogue version (CMS-CACHE-003).
 */
final class ProjectCards
{
    /** Relations every card needs: area, its city and the cover with its media row. */
    public const array WITH = ['location.parent', 'cover.media'];

    /**
     * Published projects, with the relations a card needs.
     *
     * @return Builder<Project>
     */
    public static function query(): Builder
    {
        return Project::query()->published()->with(self::WITH);
    }

    /**
     * The `featured_projects` preset: featured first in the editors' order (FR-PROJ-008).
     *
     * @return list<array<string, mixed>>
     */
    public static function featured(?int $limit = null): array
    {
        return CatalogCache::remember("projects:featured:{$limit}", CatalogCache::LISTING_TTL, fn (): array => self::cards(
            self::query()->where('is_featured', true)->orderBy('featured_order')->orderByDesc('published_at')
                ->when($limit !== null, fn (Builder $query) => $query->limit($limit))->get(),
        ));
    }

    /**
     * The `project_grid` preset: newest first within a segment, lifecycle and/or area.
     *
     * @return list<array<string, mixed>>
     */
    public static function filtered(?Segment $segment = null, ?Lifecycle $lifecycle = null, ?int $limit = null, ?int $areaId = null): array
    {
        $key = 'projects:grid:'.($segment->value ?? '-').':'.($lifecycle->value ?? '-').":{$limit}".($areaId === null ? '' : ":area{$areaId}");

        return CatalogCache::remember($key, CatalogCache::LISTING_TTL, fn (): array => self::cards(
            self::query()
                ->when($segment !== null, fn (Builder $query) => $query->where('segment', $segment->value))
                ->when($lifecycle !== null, fn (Builder $query) => $query->where('lifecycle', $lifecycle->value))
                ->when($areaId !== null, fn (Builder $query) => $query->where('location_id', $areaId))
                ->orderByDesc('published_at')->orderByDesc('id')
                ->when($limit !== null, fn (Builder $query) => $query->limit($limit))->get(),
        ));
    }

    /**
     * The newest published projects (AC-SRCH-04 empty state).
     *
     * @return list<array<string, mixed>>
     */
    public static function newest(int $limit = 3): array
    {
        return self::filtered(limit: $limit);
    }

    /**
     * Cards in the order of the given IDs; unknown or unpublished IDs are left out.
     *
     * @param  list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public static function byIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $projects = self::query()->whereIn('id', $ids)->get()->keyBy('id');

        return self::cards(new Collection(array_values(array_filter(array_map(fn (int $id): ?Project => $projects[$id] ?? null, $ids)))));
    }

    /**
     * @param  Collection<int, Project>  $projects  with {@see self::WITH} loaded
     * @return list<array<string, mixed>>
     */
    public static function cards(Collection $projects): array
    {
        return array_values($projects->map(fn (Project $project): array => ProjectCardResource::make($project)->resolve())->all());
    }
}
