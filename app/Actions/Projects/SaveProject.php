<?php

namespace App\Actions\Projects;

use App\Actions\Activity\LogActivity;
use App\Actions\Media\SyncMediaUsages;
use App\Actions\Seo\SaveSeoMeta;
use App\Enums\PublishStatus;
use App\Models\Project;
use App\Models\ProjectProgressUpdate;
use App\Models\ProjectUnitType;
use App\Models\User;
use App\Support\Projects\ProjectAttributes;
use Illuminate\Support\Facades\DB;

/**
 * ADM-SCR-01 / FR-PROJ-008: creates or updates the live row of a project with everything the editor
 * holds — facts, content, media, unit types, amenities, progress updates, nearby places, documents,
 * pricing, lead routing, similar projects and SEO — in one transaction (the Form Request has
 * checked the shape).
 *
 * Saving never publishes: a new project is a Draft and an update keeps its status (the publish
 * checklist belongs to `PublishEntity`, VAL-05). Whether an edit of a published project goes to its
 * pending revision instead is decided one level up (`SaveEntityChanges`, CMS-PUB-002); this Action is
 * also what applies a pending revision when it is published. Unit types and progress updates are
 * matched by `id` so their floor plans and photos keep their usage rows; the other repeaters are
 * replaced. Ranges and `price_from_bdt` are derived (`RefreshProjectDerivedFields`) and never taken
 * from the form.
 */
final class SaveProject
{
    public function __construct(
        private readonly SaveSeoMeta $saveSeoMeta,
        private readonly SyncMediaUsages $syncMediaUsages,
        private readonly SyncProjectMediaUsages $syncProjectMediaUsages,
        private readonly RefreshProjectDerivedFields $refreshDerivedFields,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input of `SaveProjectRequest`
     * @param  bool  $log  false for an autosave and for applying a revision (the caller logs)
     */
    public function handle(?User $actor, array $data, ?Project $project = null, bool $log = true): Project
    {
        return DB::transaction(function () use ($actor, $data, $project, $log): Project {
            $creating = $project === null;
            $project ??= new Project(['publish_status' => PublishStatus::Draft]);

            $project->fill(ProjectAttributes::fromPayload($data));

            if ($project->is_featured) {
                $project->featured_order ??= ((int) Project::query()->where('is_featured', true)->max('featured_order')) + 1;
            } else {
                $project->featured_order = null;
            }

            $changed = array_keys($project->getDirty());

            $project->bumpLock($actor);
            $project->save();

            $this->syncUnitTypes($project, $data['unit_types'] ?? []);
            $this->syncAmenities($project, $data['amenity_ids'] ?? []);
            $this->syncProgress($project, $data['progress_updates'] ?? []);
            $this->replaceNearby($project, $data['nearby_places'] ?? []);
            $this->replaceDocuments($project, $data['documents'] ?? []);
            $this->syncSimilar($project, $data['similar_ids'] ?? []);
            $this->saveSeoMeta->handle($project, $data['seo'] ?? null);

            $project->unsetRelations();
            $project->load(['documents', 'unitTypes']);
            $this->syncProjectMediaUsages->handle($project);
            $this->refreshDerivedFields->handle($project);

            if ($log) {
                $this->logActivity->handle(
                    'projects',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Project created: ' : 'Project updated: ').$project->name,
                    $actor,
                    $project,
                    ['label' => $project->name, 'fields' => $creating ? [] : array_values(array_diff($changed, ['updated_at', 'lock_version', 'edited_by']))],
                );
            }

            return $project;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $units
     */
    private function syncUnitTypes(Project $project, array $units): void
    {
        $keep = [];

        foreach ($units as $position => $row) {
            $unit = isset($row['id']) ? $project->unitTypes()->whereKey($row['id'])->first() : null;
            $unit ??= new ProjectUnitType(['project_id' => $project->id]);

            $unit->fill(ProjectAttributes::unit($row, $position))->save();

            $keep[] = $unit->id;
        }

        // Model by model, so each delete refreshes the ranges and drops the plan's usage rows.
        $project->unitTypes()->whereNotIn('id', $keep)->get()->each->delete();
    }

    /**
     * @param  list<int>  $amenityIds  in display order
     */
    private function syncAmenities(Project $project, array $amenityIds): void
    {
        $sync = [];

        foreach (array_values(array_unique($amenityIds)) as $position => $id) {
            $sync[$id] = ['sort_order' => $position];
        }

        $project->amenities()->sync($sync);
    }

    /**
     * @param  list<array<string, mixed>>  $updates
     */
    private function syncProgress(Project $project, array $updates): void
    {
        $keep = [];

        foreach ($updates as $row) {
            $update = isset($row['id']) ? $project->progressUpdates()->whereKey($row['id'])->first() : null;
            $update ??= new ProjectProgressUpdate(['project_id' => $project->id]);

            $update->fill(ProjectAttributes::progress($row))->save();

            $this->syncMediaUsages->handle($update, 'photos', array_values(array_map(intval(...), $row['photo_ids'] ?? [])));
            $keep[] = $update->id;
        }

        $project->progressUpdates()->whereNotIn('id', $keep)->get()->each->delete();
    }

    /**
     * @param  list<array<string, mixed>>  $places
     */
    private function replaceNearby(Project $project, array $places): void
    {
        $project->nearbyPlaces()->delete();

        foreach ($places as $position => $place) {
            $project->nearbyPlaces()->create(ProjectAttributes::nearby($place, $position));
        }
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     */
    private function replaceDocuments(Project $project, array $documents): void
    {
        $project->documents()->delete();

        foreach ($documents as $position => $document) {
            $project->documents()->create(ProjectAttributes::document($document, $position));
        }
    }

    /**
     * @param  list<int>  $similarIds
     */
    private function syncSimilar(Project $project, array $similarIds): void
    {
        $sync = [];

        foreach (array_values(array_unique(array_diff($similarIds, [$project->id]))) as $position => $id) {
            $sync[$id] = ['sort_order' => $position];
        }

        $project->similar()->sync($sync);
    }
}
