<?php

namespace App\Queries\Admin;

use App\Models\Location;
use App\Models\Project;
use App\Support\Locations\Indexability;

/**
 * ADM-SCR-04 / CMP-ADM-TREE: the cities with their areas, in the order editors dragged them, with the
 * numbers each row shows (projects, indexability) and what blocks its deletion (DEL-03).
 */
final class LocationTree
{
    private const int USAGE_LIST = 8;

    public function __construct(private readonly Indexability $indexability) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function nodes(): array
    {
        $all = Project::query()->selectRaw('location_id, count(*) as total')->groupBy('location_id')->pluck('total', 'location_id');
        $live = Project::query()->published()->selectRaw('location_id, count(*) as total')->groupBy('location_id')->pluck('total', 'location_id');

        $cities = Location::query()->cities()->with('children')->orderBy('sort_order')->orderBy('name')->get();

        return $cities->map(function (Location $city) use ($all, $live): array {
            $areas = $city->children->map(function (Location $area) use ($all, $live): array {
                $projects = (int) ($all[$area->id] ?? 0);
                $verdict = $this->indexability->for($area, (int) ($live[$area->id] ?? 0));

                return [
                    'id' => $area->id,
                    'name' => $area->name,
                    'slug' => $area->slug,
                    'status' => $area->publish_status->value,
                    'status_label' => $area->publish_status->label(),
                    'projects_count' => $projects,
                    'published_projects' => $verdict['published_projects'],
                    'indexable' => $verdict['indexable'],
                    'reasons' => $verdict['reasons'],
                    'edit_url' => route('admin.locations.areas.edit', $area->id),
                    'public_url' => $area->isPublished() ? url("/locations/{$area->slug}") : null,
                    'usage' => $projects === 0 ? null : $this->areaUsage($area, $projects),
                ];
            })->values()->all();

            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'areas_count' => count($areas),
                'projects_count' => array_sum(array_column($areas, 'projects_count')),
                'public_url' => url("/locations/{$city->slug}"),
                'areas' => $areas,
                'usage' => $areas === [] ? null : [
                    'message' => "“{$city->name}” has ".count($areas).' '.(count($areas) === 1 ? 'area' : 'areas').'. Delete or move them first.',
                    'total' => count($areas),
                    'items' => array_map(fn (array $area): array => ['label' => $area['name'], 'href' => $area['edit_url']], array_slice($areas, 0, self::USAGE_LIST)),
                    'list_label' => 'Open the area',
                ],
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function areaUsage(Location $area, int $projects): array
    {
        return [
            'message' => "“{$area->name}” has {$projects} ".($projects === 1 ? 'project' : 'projects').'. Move them to another area or delete them first.',
            'total' => $projects,
            'items' => $area->projects()->orderBy('name')->limit(self::USAGE_LIST)->get(['id', 'name'])
                ->map(fn (Project $project): array => ['label' => $project->name, 'href' => route('admin.projects.edit', $project->id)])->values()->all(),
            'list_label' => 'Open the project',
            'see_all' => route('admin.projects.index', ['filter' => ['area' => [(string) $area->id]]]),
        ];
    }
}
