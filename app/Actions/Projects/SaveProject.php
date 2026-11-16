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
use App\Support\Html\BasicHtml;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ADM-SCR-01 / FR-PROJ-008: creates or updates a project with everything the editor holds — facts,
 * content, media, unit types, amenities, progress updates, nearby places, documents, pricing, lead
 * routing, similar projects and SEO — in one transaction (the Form Request has checked the shape).
 *
 * Saving never publishes: a new project is a Draft and an update keeps its status (the publish
 * checklist belongs to `PublishProject`, VAL-05). Unit types and progress updates are matched by
 * `id` so their floor plans and photos keep their usage rows; the other repeaters are replaced.
 * Ranges and `price_from_bdt` are derived (`RefreshProjectDerivedFields`) and never taken from the form.
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
     */
    public function handle(User $actor, array $data, ?Project $project = null): Project
    {
        return DB::transaction(function () use ($actor, $data, $project): Project {
            $creating = $project === null;
            $project ??= new Project(['publish_status' => PublishStatus::Draft]);

            $project->fill($this->attributes($data));

            if ($project->is_featured) {
                $project->featured_order ??= ((int) Project::query()->where('is_featured', true)->max('featured_order')) + 1;
            } else {
                $project->featured_order = null;
            }

            $changed = array_keys($project->getDirty());

            if (! $creating) {
                $project->lock_version++;
            }

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

            $this->logActivity->handle(
                'projects',
                $creating ? 'created' : 'updated',
                ($creating ? 'Project created: ' : 'Project updated: ').$project->name,
                $actor,
                $project,
                ['label' => $project->name, 'fields' => $creating ? [] : array_values(array_diff($changed, ['updated_at', 'lock_version']))],
            );

            return $project;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $text = fn (string $key): ?string => $this->text($data[$key] ?? null);
        $number = fn (string $key): int|float|null => ($data[$key] ?? '') === '' ? null : $data[$key] + 0;

        $attributes = [
            'name' => trim((string) $data['name']),
            'slug' => (string) $data['slug'],
            'tagline' => $text('tagline'),
            'segment' => $data['segment'],
            'lifecycle' => $data['lifecycle'],
            'booking_state' => $data['booking_state'],
            'location_id' => $data['location_id'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'address' => $text('address'),
            'latitude' => $number('latitude'),
            'longitude' => $number('longitude'),
            'land_area_katha' => $number('land_area_katha'),
            'storeys' => $text('storeys'),
            'total_units' => $number('total_units'),
            'units_per_floor' => $number('units_per_floor'),
            'lifts' => $number('lifts'),
            'car_parks' => $number('car_parks'),
            'basements' => $number('basements'),
            'road_width_ft' => $number('road_width_ft'),
            'facing' => $text('facing'),
            'orientation' => $text('orientation'),
            'launch_date' => $this->month($data['launch_date'] ?? null),
            'handover_date' => $this->month($data['handover_date'] ?? null),
            'rajuk_approval_no' => $text('rajuk_approval_no'),
            'architect' => $text('architect'),
            'structural_engineer' => $text('structural_engineer'),
            'contractor' => $text('contractor'),
            'custom_facts' => $this->customFacts($data['custom_facts'] ?? []),
            'overview' => BasicHtml::clean($data['overview'] ?? null),
            'design_narrative' => BasicHtml::clean($data['design_narrative'] ?? null),
            'highlights' => $this->highlights($data['highlights'] ?? []),
            'cover_asset_id' => $data['cover_asset_id'] ?? null,
            'gallery' => $this->gallery($data['gallery'] ?? []),
            'video_type' => $data['video_type'] ?? 'none',
            'video_url' => $text('video_url'),
            'video_asset_id' => $data['video_asset_id'] ?? null,
            'video_poster_asset_id' => $data['video_poster_asset_id'] ?? null,
            'video_captions_asset_id' => $data['video_captions_asset_id'] ?? null,
            'virtual_tour_url' => $text('virtual_tour_url'),
            'show_price' => (bool) ($data['show_price'] ?? false),
            'price_from_manual' => (bool) ($data['price_from_manual'] ?? false),
            'price_note' => $text('price_note'),
            'brochure_gated' => (bool) ($data['brochure_gated'] ?? true),
            'default_assignee_id' => $data['default_assignee_id'] ?? null,
            'whatsapp_override' => $text('whatsapp_override'),
        ];

        // The video fields that do not belong to the chosen type are dropped.
        $type = (string) $attributes['video_type'];

        if ($type !== 'youtube') {
            $attributes['video_url'] = null;
        }

        if ($type !== 'upload') {
            $attributes['video_asset_id'] = null;
            $attributes['video_captions_asset_id'] = null;
        }

        if ($type === 'none') {
            $attributes['video_poster_asset_id'] = null;
        }

        // `price_from_bdt` is derived from the unit types unless the editor set it by hand (land).
        if ($attributes['price_from_manual']) {
            $attributes['price_from_bdt'] = ($data['price_from_bdt'] ?? '') === '' ? null : (int) $data['price_from_bdt'];
        }

        return $attributes;
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

            $unit->fill([
                'name' => trim((string) $row['name']),
                'size_sft' => (int) $row['size_sft'],
                'size_sft_max' => $this->integer($row['size_sft_max'] ?? null),
                'bedrooms' => $this->integer($row['bedrooms'] ?? null),
                'bathrooms' => $this->integer($row['bathrooms'] ?? null),
                'balconies' => $this->integer($row['balconies'] ?? null),
                'facing' => $this->text($row['facing'] ?? null),
                'floor_range' => $this->text($row['floor_range'] ?? null),
                'floor_plan_asset_id' => $row['floor_plan_asset_id'] ?? null,
                'plan_description' => $this->text($row['plan_description'] ?? null),
                'plan_3d_asset_id' => $row['plan_3d_asset_id'] ?? null,
                'price_bdt' => $this->integer($row['price_bdt'] ?? null),
                'show_price' => (bool) ($row['show_price'] ?? false),
                'availability' => $row['availability'] ?? 'available',
                'sort_order' => $position,
            ])->save();

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

            $update->fill([
                'reported_on' => $this->month($row['reported_on']),
                'percent' => (int) $row['percent'],
                'stage' => $row['stage'],
                'note' => $this->text($row['note'] ?? null),
            ])->save();

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
            $project->nearbyPlaces()->create([
                'name' => trim((string) $place['name']),
                'category' => $place['category'],
                'minutes' => (int) $place['minutes'],
                'mode' => $place['mode'],
                'sort_order' => $position,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $documents
     */
    private function replaceDocuments(Project $project, array $documents): void
    {
        $project->documents()->delete();

        foreach ($documents as $position => $document) {
            $project->documents()->create([
                'media_asset_id' => (int) $document['asset_id'],
                'title' => trim((string) $document['title']),
                'kind' => $document['kind'],
                'is_gated' => (bool) ($document['is_gated'] ?? false),
                'sort_order' => $position,
            ]);
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

    /**
     * @param  list<array<string, mixed>>  $facts
     * @return list<array{label: string, value: string}>|null
     */
    private function customFacts(array $facts): ?array
    {
        $clean = array_values(array_map(
            fn (array $fact): array => ['label' => trim((string) $fact['label']), 'value' => trim((string) $fact['value'])],
            array_filter($facts, fn (array $fact): bool => trim((string) ($fact['label'] ?? '')) !== '' || trim((string) ($fact['value'] ?? '')) !== ''),
        ));

        return $clean === [] ? null : $clean;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{asset_id: int, title: string, text: string}>|null
     */
    private function highlights(array $items): ?array
    {
        $clean = array_map(
            fn (array $item): array => [
                'asset_id' => (int) $item['asset_id'],
                'title' => trim((string) $item['title']),
                'text' => trim((string) ($item['text'] ?? '')),
            ],
            $items,
        );

        return $clean === [] ? null : $clean;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array{asset_id: int, category: string, caption: string|null}>|null
     */
    private function gallery(array $items): ?array
    {
        $clean = array_map(
            fn (array $item): array => [
                'asset_id' => (int) $item['asset_id'],
                'category' => (string) $item['category'],
                'caption' => $this->text($item['caption'] ?? null),
            ],
            $items,
        );

        return $clean === [] ? null : $clean;
    }

    /** `YYYY-MM` from a month input → the first of that month (month precision, DM-PROJ). */
    private function month(mixed $value): ?Carbon
    {
        $value = trim((string) $value);

        return $value === '' ? null : Carbon::createFromFormat('!Y-m', substr($value, 0, 7))->startOfMonth();
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function integer(mixed $value): ?int
    {
        return ($value ?? '') === '' ? null : (int) $value;
    }
}
