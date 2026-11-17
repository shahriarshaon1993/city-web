<?php

namespace App\Actions\Publishing;

use App\Actions\Activity\LogActivity;
use App\Enums\PublishStatus;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use App\Support\Publishing\Publishing;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * CMS-PUB-003: choose when it goes live; the scheduler (every minute) does the rest.
 *
 * A Draft (or an already Scheduled) entity gets status Scheduled and `published_at` = the time; a
 * Published entity with a pending revision gets `publish_at` on that revision. The publish checklist
 * must pass now, so a schedule is never accepted for something the scheduler would then refuse.
 */
final class ScheduleEntity
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  CarbonInterface  $at  a future instant (the Form Request converts the Asia/Dhaka input)
     *
     * @throws ValidationException
     */
    public function handle(User $actor, Model&PublishableEntity $entity, CarbonInterface $at): Model&PublishableEntity
    {
        $driver = Publishing::for($entity);
        $status = $entity->getAttribute('publish_status');
        $pending = $entity->pendingRevisionOrNull();

        if ($status === PublishStatus::Archived || ($status === PublishStatus::Published && $pending === null)) {
            throw ValidationException::withMessages(['publish_at' => $status === PublishStatus::Archived ? 'Restore it first.' : 'There are no pending changes to schedule.']);
        }

        $open = array_values(array_filter(
            $driver->checklist($pending === null ? $entity : $driver->hydrate($entity, $pending->payload)),
            fn (array $item): bool => ! $item['passed'],
        ));

        if ($open !== []) {
            throw ValidationException::withMessages(['publish_at' => count($open).' checklist '.(count($open) === 1 ? 'item is' : 'items are').' still open.']);
        }

        DB::transaction(function () use ($actor, $entity, $status, $pending, $at, $driver): void {
            if ($status === PublishStatus::Published) {
                $pending->forceFill(['publish_at' => $at])->save();
                $entity->bumpLock($actor);
                $entity->saveQuietly();
            } else {
                $entity->forceFill(['publish_status' => PublishStatus::Scheduled, 'published_at' => $at]);
                $entity->bumpLock($actor);
                $entity->save();
            }

            $this->logActivity->handle(
                $driver->logName(),
                'scheduled',
                ucfirst($driver->noun()).' scheduled: '.$driver->label($entity),
                $actor,
                $entity,
                ['label' => $driver->label($entity), 'at' => $at->toIso8601String()],
            );
        });

        return $entity;
    }
}
