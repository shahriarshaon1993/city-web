<?php

namespace App\Support\Publishing;

use App\Actions\Projects\RefreshProjectDerivedFields;
use App\Actions\Seo\SaveSeoMeta;
use App\Models\Amenity;
use App\Models\Location;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Models\ProjectDocument;
use App\Models\ProjectNearbyPlace;
use App\Models\ProjectProgressUpdate;
use App\Models\ProjectUnitType;
use App\Models\SeoMeta;
use App\Support\Projects\ProjectAttributes;
use Illuminate\Support\Collection;

/**
 * An unsaved copy of a project as an editor payload would make it (CMS-PUB-002): the live row with
 * the payload's columns and every child list set as loaded relations. Nothing is written, so a
 * revision can be previewed (CMS-PUB-005), checked against the publish checklist and turned back
 * into the canonical editor shape without touching the live project.
 *
 * Mirrors what `SaveProject` stores, through the same `ProjectAttributes` mapping.
 */
final class ProjectHydrator
{
    /**
     * @param  array<string, mixed>  $payload  the editor payload (validated input or a stored revision)
     * @param  bool  $preview  give rows without an id a negative one, so a page can key them
     */
    public function handle(Project $live, array $payload, bool $preview = false): Project
    {
        $project = $live->replicate();
        $project->forceFill([...ProjectAttributes::fromPayload($payload), 'id' => $live->id, 'created_at' => $live->created_at, 'updated_at' => $live->updated_at]);
        $project->exists = true;

        $assets = $this->assets($payload);
        $placeholder = static fn (mixed $id, int $index): ?int => $id !== null ? (int) $id : ($preview ? -($index + 1) : null);

        $units = $this->rows($payload, 'unit_types')->map(fn (array $row, int $index): ProjectUnitType => (new ProjectUnitType)
            ->forceFill([...ProjectAttributes::unit($row, $index), 'id' => $placeholder($row['id'] ?? null, $index), 'project_id' => $live->id]));

        $progress = $this->rows($payload, 'progress_updates')->map(function (array $row, int $index) use ($live, $assets, $placeholder): ProjectProgressUpdate {
            $update = (new ProjectProgressUpdate)->forceFill([...ProjectAttributes::progress($row), 'id' => $placeholder($row['id'] ?? null, $index), 'project_id' => $live->id]);
            $update->setRelation('photos', new Collection(array_values(array_filter(array_map(fn ($id) => $assets->get((int) $id), $row['photo_ids'] ?? [])))));

            return $update;
        })->sort(fn (ProjectProgressUpdate $a, ProjectProgressUpdate $b): int => [$b->reported_on->getTimestamp(), $b->id] <=> [$a->reported_on->getTimestamp(), $a->id])->values();

        $nearby = $this->rows($payload, 'nearby_places')->map(fn (array $row, int $index): ProjectNearbyPlace => (new ProjectNearbyPlace)
            ->forceFill([...ProjectAttributes::nearby($row, $index), 'id' => $placeholder(null, $index), 'project_id' => $live->id]));

        $documents = $this->rows($payload, 'documents')->map(fn (array $row, int $index): ProjectDocument => (new ProjectDocument)
            ->forceFill([...ProjectAttributes::document($row, $index), 'id' => $placeholder(null, $index), 'project_id' => $live->id]));

        $project->forceFill(RefreshProjectDerivedFields::derive($units, (bool) $project->price_from_manual));

        $seoValues = SaveSeoMeta::values($payload['seo'] ?? null);
        $seo = SaveSeoMeta::isDefault($seoValues) ? null : (new SeoMeta)->forceFill($seoValues);
        $seo?->setRelation('ogImage', $assets->get((int) ($seoValues['og_image_asset_id'] ?? 0)));

        $project->setRelation('location', Location::withTrashed()->with('parent')->find($project->location_id));
        $project->setRelation('cover', $assets->get((int) $project->cover_asset_id));
        $project->setRelation('unitTypes', $units);
        $project->setRelation('amenities', $this->inOrder(Amenity::class, $payload['amenity_ids'] ?? []));
        $project->setRelation('progressUpdates', $progress);
        $project->setRelation('nearbyPlaces', $nearby);
        $project->setRelation('documents', $documents);
        $project->setRelation('similar', $this->inOrder(Project::class, array_diff($payload['similar_ids'] ?? [], [$live->id])));
        $project->setRelation('seoMeta', $seo);
        $project->syncOriginal();

        return $project;
    }

    /**
     * The rows of a repeater field of the payload.
     *
     * @param  array<string, mixed>  $payload
     * @return Collection<int, array<string, mixed>>
     */
    private function rows(array $payload, string $key): Collection
    {
        $rows = [];

        foreach ((array) ($payload[$key] ?? []) as $row) {
            if (is_array($row)) {
                $rows[] = array_combine(array_map(strval(...), array_keys($row)), array_values($row));
            }
        }

        return new Collection($rows);
    }

    /**
     * Every library item the payload points at that a page needs the files of, by id.
     *
     * @param  array<string, mixed>  $payload
     * @return Collection<int, MediaAsset>
     */
    private function assets(array $payload): Collection
    {
        $ids = array_filter([
            $payload['cover_asset_id'] ?? null,
            $payload['seo']['og_image_asset_id'] ?? null,
            ...array_merge([], ...array_map(fn (array $row): array => $row['photo_ids'] ?? [], $payload['progress_updates'] ?? [])),
        ]);

        return $ids === [] ? collect() : MediaAsset::query()->whereKey(array_unique($ids))->with('media')->get()->keyBy('id');
    }

    /**
     * The rows of `$class` with these ids, in the given order (a missing id is skipped).
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  class-string<TModel>  $class
     * @param  array<int, int|string>  $ids
     * @return Collection<int, TModel>
     */
    private function inOrder(string $class, array $ids): Collection
    {
        $ids = array_values(array_unique(array_map(intval(...), $ids)));

        if ($ids === []) {
            return new Collection;
        }

        $rows = $class::query()->whereKey($ids)->get()->keyBy('id');

        return collect($ids)->map(fn (int $id) => $rows->get($id))->filter()->values();
    }
}
