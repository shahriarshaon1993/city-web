<?php

namespace App\Support\Projects;

use App\Enums\Segment;
use App\Models\MediaAsset;
use App\Models\Project;
use App\Support\Html\BasicHtml;
use App\Support\Media\MediaKind;

/**
 * AC-PROJ-03 / VAL-05: the publish-only rules of FR-PROJ, each with the editor tab and field that
 * fixes it (CMP-ADM-CHECKLIST "Fix" links). Saving a draft is never blocked by these; Publish is.
 * The checklist reads the saved project, so unsaved edits do not count until they are saved.
 */
final class PublishChecklist
{
    /** Bangladesh bounding box (spec/projects.md validation table). */
    public const float LAT_MIN = 20.5;

    public const float LAT_MAX = 26.7;

    public const float LNG_MIN = 88.0;

    public const float LNG_MAX = 92.7;

    /**
     * @return list<array{key: string, label: string, passed: bool, detail: string|null, tab: string, field: string}>
     */
    public function items(Project $project): array
    {
        $project->loadMissing(['unitTypes', 'seoMeta']);

        $gallery = $project->gallery ?? [];
        $overviewWords = BasicHtml::wordCount($project->overview);
        $hasCoordinates = $project->latitude !== null && $project->longitude !== null
            && $project->latitude >= self::LAT_MIN && $project->latitude <= self::LAT_MAX
            && $project->longitude >= self::LNG_MIN && $project->longitude <= self::LNG_MAX;
        $needsUnits = $project->segment !== Segment::Land;
        $missingAlt = $this->imagesMissingAlt(array_values(array_filter([$project->cover_asset_id, ...array_column($gallery, 'asset_id')])));
        $unitsWithoutPlan = $project->unitTypes->filter(fn ($unit): bool => $unit->floor_plan_asset_id === null)->count();
        $description = trim((string) $project->seoMeta?->meta_description);

        return [
            $this->item('cover', 'Cover image', $project->cover_asset_id !== null, null, 'media', 'cover_asset_id'),
            $this->item('gallery', 'At least 4 gallery images', count($gallery) >= Project::GALLERY_MIN_TO_PUBLISH, count($gallery).' of '.Project::GALLERY_MIN_TO_PUBLISH, 'media', 'gallery'),
            $this->item('alt', 'Alt text on the cover and gallery images', $missingAlt === 0, $missingAlt === 0 ? null : "{$missingAlt} without alt text", 'media', 'gallery'),
            $this->item('overview', 'Overview of at least 120 words', $overviewWords >= 120, "{$overviewWords} of 120 words", 'content', 'overview'),
            $this->item('location', 'Location (area)', $project->location_id > 0, null, 'basics', 'location_id'),
            $this->item('coordinates', 'Map position inside Bangladesh', $hasCoordinates, null, 'facts', 'latitude'),
            $this->item(
                'units',
                $needsUnits ? 'At least 1 unit type' : 'Unit types (not needed for land)',
                ! $needsUnits || $project->unitTypes->isNotEmpty(),
                null,
                'units',
                'unit_types',
            ),
            $this->item('plans', 'A floor plan on every unit type', $unitsWithoutPlan === 0, $unitsWithoutPlan === 0 ? null : "{$unitsWithoutPlan} without a plan", 'units', 'unit_types'),
            $this->item(
                'meta',
                'Meta description',
                $description !== '' || ($project->name !== '' && $project->location_id > 0),
                $description === '' ? 'The automatic description is used.' : null,
                'seo',
                'seo.meta_description',
            ),
        ];
    }

    /**
     * @return list<array{key: string, label: string, passed: bool, detail: string|null, tab: string, field: string}>
     */
    public function failing(Project $project): array
    {
        return array_values(array_filter($this->items($project), fn (array $item): bool => ! $item['passed']));
    }

    /**
     * @param  list<int>  $assetIds
     */
    private function imagesMissingAlt(array $assetIds): int
    {
        if ($assetIds === []) {
            return 0;
        }

        return MediaAsset::query()
            ->whereKey(array_unique($assetIds))
            ->whereIn('kind', [MediaKind::Image->value, MediaKind::Svg->value])
            ->where('is_decorative', false)
            ->where(fn ($query) => $query->whereNull('alt')->orWhere('alt', ''))
            ->count();
    }

    /**
     * @return array{key: string, label: string, passed: bool, detail: string|null, tab: string, field: string}
     */
    private function item(string $key, string $label, bool $passed, ?string $detail, string $tab, string $field): array
    {
        return ['key' => $key, 'label' => $label, 'passed' => $passed, 'detail' => $detail, 'tab' => $tab, 'field' => $field];
    }
}
