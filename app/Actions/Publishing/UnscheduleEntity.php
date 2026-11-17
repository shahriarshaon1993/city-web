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

/** Workflow "Scheduled → Draft": the date is dropped. For a published entity only the schedule of its pending changes goes. */
final class UnscheduleEntity
{
    public function __construct(private readonly LogActivity $logActivity) {}

    public function handle(User $actor, Model&PublishableEntity $entity): Model&PublishableEntity
    {
        $status = $entity->getAttribute('publish_status');
        $pendingId = $entity->getAttribute('pending_revision_id');

        if ($status !== PublishStatus::Scheduled && ($status !== PublishStatus::Published || $pendingId === null)) {
            return $entity;
        }

        DB::transaction(function () use ($actor, $entity, $status, $pendingId): void {
            $driver = Publishing::for($entity);

            if ($status === PublishStatus::Scheduled) {
                $entity->forceFill(['publish_status' => PublishStatus::Draft, 'published_at' => null]);
                $entity->bumpLock($actor);
                $entity->save();
            } else {
                Revision::query()->whereKey($pendingId)->update(['publish_at' => null]);
                $entity->bumpLock($actor);
                $entity->saveQuietly();
            }

            $this->logActivity->handle($driver->logName(), 'unscheduled', ucfirst($driver->noun()).' unscheduled: '.$driver->label($entity), $actor, $entity, ['label' => $driver->label($entity)]);
        });

        return $entity;
    }
}
