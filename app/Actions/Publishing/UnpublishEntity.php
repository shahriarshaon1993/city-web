<?php

namespace App\Actions\Publishing;

use App\Actions\Activity\LogActivity;
use App\Enums\PublishStatus;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use App\Support\Publishing\Publishing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * CRUD-08: Unpublish = back to Draft, not public. A pending revision stays, so the editor still opens
 * with its changes; a schedule on it is cleared.
 */
final class UnpublishEntity
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Model&PublishableEntity $entity): Model&PublishableEntity
    {
        if ($entity->getAttribute('publish_status') === PublishStatus::Draft) {
            return $entity;
        }

        DB::transaction(function () use ($actor, $entity): void {
            $driver = Publishing::for($entity);

            $entity->forceFill(['publish_status' => PublishStatus::Draft]);
            $entity->bumpLock($actor);
            $entity->save();

            if ($entity->getAttribute('pending_revision_id') !== null) {
                Revision::query()->whereKey($entity->getAttribute('pending_revision_id'))->update(['publish_at' => null]);
            }

            $this->logActivity->handle($driver->logName(), 'unpublished', ucfirst($driver->noun()).' unpublished: '.$driver->label($entity), $actor, $entity, ['label' => $driver->label($entity)]);
        });

        return $entity;
    }
}
