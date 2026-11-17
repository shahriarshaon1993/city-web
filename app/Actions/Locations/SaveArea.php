<?php

namespace App\Actions\Locations;

use App\Actions\Activity\LogActivity;
use App\Actions\Seo\SaveSeoMeta;
use App\Enums\LocationType;
use App\Enums\PublishStatus;
use App\Models\Location;
use App\Models\User;
use App\Support\Locations\AreaAttributes;
use Illuminate\Support\Facades\DB;

/**
 * FR-LOC-002: creates or updates the live row of an area with its intro, facts, optional per-segment
 * intros (FR-LOC-004), map centre, nearby areas, hero image and SEO, in one transaction. Whether the
 * page is indexable (FR-LOC-003) is derived on read (`Support\Locations\Indexability`), never stored.
 *
 * Like projects, saving never publishes: a new area is a Draft, an update keeps its status, and an
 * edit of a published area goes to its pending revision one level up (`SaveEntityChanges`, CMS-PUB-002).
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
     * @param  bool  $log  false for an autosave and for applying a revision (the caller logs)
     */
    public function handle(?User $actor, array $data, ?Location $area = null, bool $log = true): Location
    {
        return DB::transaction(function () use ($actor, $data, $area, $log): Location {
            $creating = $area === null;
            $area ??= new Location(['type' => LocationType::Area, 'publish_status' => PublishStatus::Draft]);

            if ($creating || $area->parent_id !== (int) $data['parent_id']) {
                $area->sort_order = ((int) Location::query()->where('parent_id', $data['parent_id'])->max('sort_order')) + 1;
            }

            $area->fill(AreaAttributes::fromPayload($data));

            $changed = array_keys($area->getDirty());
            $area->bumpLock($actor);
            $area->save();

            $nearby = [];

            foreach (array_values(array_unique($data['nearby_ids'] ?? [])) as $position => $id) {
                $nearby[$id] = ['sort_order' => $position];
            }

            $area->nearby()->sync($nearby);
            $this->saveSeoMeta->handle($area, $data['seo'] ?? null);
            $this->syncLocationMediaUsages->handle($area);

            if ($log) {
                $this->logActivity->handle(
                    'locations',
                    $creating ? 'created' : 'updated',
                    ($creating ? 'Area created: ' : 'Area updated: ').$area->name,
                    $actor,
                    $area,
                    ['label' => $area->name, 'fields' => $creating ? [] : array_values(array_diff($changed, ['updated_at', 'lock_version', 'edited_by']))],
                );
            }

            return $area;
        });
    }
}
