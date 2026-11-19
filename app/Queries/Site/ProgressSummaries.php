<?php

namespace App\Queries\Site;

use App\Enums\Lifecycle;
use App\Http\Resources\Media\MediaImageResource;
use App\Models\Project;
use App\Models\ProjectProgressUpdate;
use App\Support\Catalog\CatalogCache;

/**
 * CMP-PROG-01 summaries: the latest progress update of each project, most recently reported first.
 * Without ids: the ongoing projects, 3 at most; with them: exactly those projects (published, with an
 * update), in the given order. Cached under the catalogue version.
 */
final class ProgressSummaries
{
    /**
     * @param  list<int>|null  $projectIds
     * @return list<array<string, mixed>>
     */
    public static function latest(?array $projectIds = null): array
    {
        $key = 'projects:progress-summaries'.($projectIds === null ? '' : ':'.implode('-', $projectIds));

        return CatalogCache::remember($key, CatalogCache::LISTING_TTL, function () use ($projectIds): array {
            $updates = [];

            $projects = Project::query()->published()
                ->when($projectIds === null, fn ($query) => $query->where('lifecycle', Lifecycle::Ongoing->value), fn ($query) => $query->whereIn('id', $projectIds))
                ->with([...ProjectCards::WITH, 'progressUpdates.photos.media'])
                ->get();

            foreach ($projects as $project) {
                $update = $project->progressUpdates->first();

                if ($update instanceof ProjectProgressUpdate) {
                    $updates[] = [$project, $update];
                }
            }

            if ($projectIds === null) {
                usort($updates, fn (array $a, array $b): int => $b[1]->reported_on <=> $a[1]->reported_on);
                $updates = array_slice($updates, 0, 3);
            } else {
                usort($updates, fn (array $a, array $b): int => array_search($a[0]->id, $projectIds, true) <=> array_search($b[0]->id, $projectIds, true));
            }

            return array_map(fn (array $pair): array => [
                'project' => [
                    'id' => $pair[0]->id,
                    'name' => $pair[0]->name,
                    'url' => '/projects/'.$pair[0]->slug,
                    'location' => ['area' => $pair[0]->location->name, 'city' => $pair[0]->location->parent->name ?? ''],
                ],
                'percent' => $pair[1]->percent,
                'stage' => $pair[1]->stage->value,
                'stage_label' => $pair[1]->stage->label(),
                'reported_on' => $pair[1]->reported_on->toDateString(),
                'note' => $pair[1]->note,
                'photo' => ($photo = $pair[1]->photos->first()) === null ? null : MediaImageResource::make($photo)->resolve(),
            ], $updates);
        });
    }
}
