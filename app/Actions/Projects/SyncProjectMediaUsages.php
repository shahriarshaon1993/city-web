<?php

namespace App\Actions\Projects;

use App\Actions\Media\SyncMediaUsages;
use App\Models\Project;
use App\Models\ProjectDocument;

/**
 * DM-MUSE / DEC-78: writes the usage rows for every library item a project holds — cover, gallery,
 * highlights, video, poster, captions, documents, the SEO share image, and each unit type's floor plan
 * and 3D plan — so the library lists where a file is used and refuses to delete it (CMS-MED-008).
 * The project's save Action calls this inside its transaction; progress photos are synced by whoever
 * writes the progress update.
 */
final class SyncProjectMediaUsages
{
    public function __construct(private readonly SyncMediaUsages $syncMediaUsages) {}

    public function handle(Project $project): void
    {
        $fields = [
            'cover' => $this->single($project->cover_asset_id),
            'gallery' => $this->assetIds($project->gallery),
            'highlights' => $this->assetIds($project->highlights),
            'video' => $this->single($project->video_asset_id),
            'video_poster' => $this->single($project->video_poster_asset_id),
            'video_captions' => $this->single($project->video_captions_asset_id),
            'documents' => array_values($project->documents->map(fn (ProjectDocument $document): int => $document->media_asset_id)->all()),
            'seo_og' => $this->single($project->seoMeta()->value('og_image_asset_id')),
        ];

        foreach ($fields as $field => $assetIds) {
            $this->syncMediaUsages->handle($project, $field, $assetIds);
        }

        foreach ($project->unitTypes as $unit) {
            $this->syncMediaUsages->handle($unit, 'floor_plan', $this->single($unit->floor_plan_asset_id));
            $this->syncMediaUsages->handle($unit, 'plan_3d', $this->single($unit->plan_3d_asset_id));
        }
    }

    /**
     * @param  list<array{asset_id: int}>|null  $items
     * @return list<int>
     */
    private function assetIds(?array $items): array
    {
        return array_map(fn (array $item): int => $item['asset_id'], $items ?? []);
    }

    /**
     * @return list<int>
     */
    private function single(?int $id): array
    {
        return $id === null ? [] : [$id];
    }
}
