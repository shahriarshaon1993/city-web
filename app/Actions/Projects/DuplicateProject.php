<?php

namespace App\Actions\Projects;

use App\Actions\Activity\LogActivity;
use App\Actions\Media\SyncMediaUsages;
use App\Enums\PublishStatus;
use App\Models\Project;
use App\Models\ProjectProgressUpdate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CRUD-05: Duplicate project = a Draft "Copy of {name}" with a free slug (`-copy`, `-copy-2`…) that
 * keeps the media references, unit types, amenities, progress, nearby places, documents, similar
 * projects and SEO. Revisions, leads, slug history and the featured flag are not copied.
 */
final class DuplicateProject
{
    public function __construct(
        private readonly SyncProjectMediaUsages $syncProjectMediaUsages,
        private readonly RefreshProjectDerivedFields $refreshDerivedFields,
        private readonly SyncMediaUsages $syncMediaUsages,
        private readonly LogActivity $logActivity,
    ) {}

    public function handle(User $actor, Project $source): Project
    {
        return DB::transaction(function () use ($actor, $source): Project {
            $source->load(['unitTypes', 'progressUpdates', 'nearbyPlaces', 'documents', 'amenities', 'similar', 'seoMeta']);

            $copy = $source->replicate(['published_at', 'pending_revision_id', 'archived_redirect_to']);
            $copy->forceFill([
                'name' => $this->freeName($source->name),
                'slug' => $this->freeSlug($source->slug),
                'publish_status' => PublishStatus::Draft,
                'published_at' => null,
                'is_featured' => false,
                'featured_order' => null,
                'lock_version' => 0,
            ])->save();

            foreach ($source->unitTypes as $unit) {
                $copy->unitTypes()->create($unit->only($unit->getFillable()));
            }

            foreach ($source->progressUpdates as $update) {
                $new = $copy->progressUpdates()->create($update->only((new ProjectProgressUpdate)->getFillable()));
                $this->syncMediaUsages->handle($new, 'photos', array_values($update->photos()->pluck('media_assets.id')->map(intval(...))->all()));
            }

            foreach ($source->nearbyPlaces as $place) {
                $copy->nearbyPlaces()->create($place->only($place->getFillable()));
            }

            foreach ($source->documents as $document) {
                $copy->documents()->create($document->only($document->getFillable()));
            }

            $copy->amenities()->sync($this->pivotOrder('amenity_project', 'amenity_id', $source));
            $copy->similar()->sync($this->pivotOrder('project_similar', 'similar_id', $source));

            if ($source->seoMeta !== null) {
                $copy->seoMeta()->create($source->seoMeta->only($source->seoMeta->getFillable()));
            }

            $copy->unsetRelations();
            $copy->load(['documents', 'unitTypes']);
            $this->syncProjectMediaUsages->handle($copy);
            $this->refreshDerivedFields->handle($copy);

            $this->logActivity->handle('projects', 'created', "Project duplicated: {$copy->name}", $actor, $copy, ['label' => $copy->name, 'source' => $source->name]);

            return $copy;
        });
    }

    /**
     * The pivot rows of one relation as `id => ['sort_order' => n]`, ready for `sync()`.
     *
     * @return array<int, array{sort_order: int}>
     */
    private function pivotOrder(string $table, string $column, Project $source): array
    {
        $rows = [];

        foreach (DB::table($table)->where('project_id', $source->id)->get([$column, 'sort_order']) as $row) {
            $rows[(int) $row->{$column}] = ['sort_order' => (int) $row->sort_order];
        }

        return $rows;
    }

    private function freeName(string $name): string
    {
        $base = mb_substr("Copy of {$name}", 0, Project::NAME_MAX - 4);
        $candidate = $base;

        for ($number = 2; Project::query()->where('name', $candidate)->exists(); $number++) {
            $candidate = "{$base} ({$number})";
        }

        return $candidate;
    }

    /** Free among all projects, the bin included (DEL-08). */
    private function freeSlug(string $slug): string
    {
        $base = mb_substr($slug, 0, Project::SLUG_MAX - 9);
        $candidate = "{$base}-copy";

        for ($number = 2; Project::withTrashed()->where('slug', $candidate)->exists(); $number++) {
            $candidate = "{$base}-copy-{$number}";
        }

        return $candidate;
    }
}
