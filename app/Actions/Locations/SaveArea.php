<?php

namespace App\Actions\Locations;

use App\Actions\Activity\LogActivity;
use App\Actions\Seo\SaveSeoMeta;
use App\Enums\LocationType;
use App\Enums\PublishStatus;
use App\Models\Location;
use App\Models\User;
use App\Support\Html\BasicHtml;
use Illuminate\Support\Facades\DB;

/**
 * FR-LOC-002: creates or updates an area with its intro, facts, optional per-segment intros
 * (FR-LOC-004), map centre, nearby areas, hero image and SEO, in one transaction. Whether the page
 * is indexable (FR-LOC-003) is derived on read (`Support\Locations\Indexability`), never stored.
 */
final class SaveArea
{
    public function __construct(
        private readonly SaveSeoMeta $saveSeoMeta,
        private readonly SyncLocationMediaUsages $syncLocationMediaUsages,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @param  array<string, mixed>  $data  validated input of `SaveAreaRequest`
     */
    public function handle(User $actor, array $data, ?Location $area = null): Location
    {
        return DB::transaction(function () use ($actor, $data, $area): Location {
            $creating = $area === null;
            $area ??= new Location(['type' => LocationType::Area]);

            if ($creating) {
                $area->sort_order = ((int) Location::query()->where('parent_id', $data['parent_id'])->max('sort_order')) + 1;
            } elseif ($area->parent_id !== (int) $data['parent_id']) {
                $area->sort_order = ((int) Location::query()->where('parent_id', $data['parent_id'])->max('sort_order')) + 1;
            }

            $status = PublishStatus::from($data['publish_status']);

            $area->fill([
                'parent_id' => $data['parent_id'],
                'name' => trim($data['name']),
                'slug' => $data['slug'],
                'intro' => BasicHtml::clean($data['intro'] ?? null),
                'segment_intros' => $this->segmentIntros($data['segment_intros'] ?? []),
                'facts' => $this->facts($data['facts'] ?? []),
                'hero_asset_id' => $data['hero_asset_id'] ?? null,
                'latitude' => ($data['latitude'] ?? '') === '' ? null : (float) $data['latitude'],
                'longitude' => ($data['longitude'] ?? '') === '' ? null : (float) $data['longitude'],
                'zoom' => (int) $data['zoom'],
                'publish_status' => $status,
                'published_at' => $status === PublishStatus::Published ? ($area->published_at ?? now()) : $area->published_at,
            ]);

            $changed = array_keys($area->getDirty());
            $area->save();

            $nearby = [];

            foreach (array_values(array_unique($data['nearby_ids'] ?? [])) as $position => $id) {
                $nearby[$id] = ['sort_order' => $position];
            }

            $area->nearby()->sync($nearby);
            $this->saveSeoMeta->handle($area, $data['seo'] ?? null);
            $this->syncLocationMediaUsages->handle($area);

            $this->logActivity->handle(
                'locations',
                $creating ? 'created' : 'updated',
                ($creating ? 'Area created: ' : 'Area updated: ').$area->name,
                $actor,
                $area,
                ['label' => $area->name, 'fields' => $creating ? [] : array_values(array_diff($changed, ['updated_at']))],
            );

            return $area;
        });
    }

    /**
     * @param  list<array{label: string, value: string}>  $facts
     * @return list<array{label: string, value: string}>|null
     */
    private function facts(array $facts): ?array
    {
        $clean = array_map(
            fn (array $fact): array => ['label' => trim($fact['label']), 'value' => trim($fact['value'])],
            $facts,
        );

        return $clean === [] ? null : $clean;
    }

    /**
     * @param  array<string, string|null>  $intros  segment value → intro
     * @return array<string, string>|null
     */
    private function segmentIntros(array $intros): ?array
    {
        $clean = [];

        foreach ($intros as $segment => $intro) {
            $html = BasicHtml::clean($intro);

            if ($html !== null) {
                $clean[$segment] = $html;
            }
        }

        return $clean === [] ? null : $clean;
    }
}
