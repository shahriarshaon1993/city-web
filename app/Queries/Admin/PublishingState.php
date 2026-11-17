<?php

namespace App\Queries\Admin;

use App\Enums\PublishStatus;
use App\Models\Contracts\PublishableEntity;
use App\Models\Revision;
use App\Models\User;
use App\Support\Auth\SystemRole;
use App\Support\Publishing\Publishing;
use Illuminate\Database\Eloquent\Model;

/**
 * What an entity editor needs about the publishing workflow (CMS-PUB), read once per page: the form
 * the editor starts from (the pending revision when there is one, else the saved row), the checklist
 * of that working copy, and the status block of the rail (pending changes, schedule, lock, links).
 */
final class PublishingState
{
    /**
     * The editor's starting values: the live row's form with the pending revision laid over it.
     *
     * @return array<string, mixed>
     */
    public static function form(Model&PublishableEntity $entity): array
    {
        $live = Publishing::for($entity)->snapshot($entity);
        $pending = self::pending($entity);

        return $pending === null ? $live : array_replace($live, $pending->payload);
    }

    /**
     * The publish checklist of the working copy (what Publish would put live), not of the saved row.
     *
     * @return list<array{key: string, label: string, passed: bool, detail: string|null, tab: string, field: string}>
     */
    public static function checklist(Model&PublishableEntity $entity): array
    {
        $driver = Publishing::for($entity);
        $pending = self::pending($entity);

        return $driver->checklist($pending === null ? $entity : $driver->hydrate($entity, $pending->payload));
    }

    /**
     * @return array<string, mixed>
     */
    public static function workflow(Model&PublishableEntity $entity, User $actor): array
    {
        $driver = Publishing::for($entity);
        $pending = self::pending($entity);
        $status = $entity->getAttribute('publish_status');
        $live = $entity->isPublished();

        return [
            'type' => $driver->type(),
            'id' => $entity->getKey(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'lock_version' => (int) $entity->getAttribute('lock_version'),
            'pending' => $pending === null ? null : [
                'id' => $pending->id,
                'by' => $pending->author?->name,
                'at' => $pending->created_at?->toIso8601String(),
                'publish_at' => $pending->publish_at?->toIso8601String(),
                'note' => $pending->note,
            ],
            'scheduled_at' => $status === PublishStatus::Scheduled ? $entity->getAttribute('published_at')?->toIso8601String() : null,
            'published_at' => $live ? $entity->getAttribute('published_at')?->toIso8601String() : null,
            'public_url' => $live ? url($driver->publicPath($entity)) : null,
            'preview_url' => route('admin.publishing.preview', [$driver->type(), $entity->getKey()]),
            'can_overwrite' => $actor->hasAnyRole([SystemRole::Admin->value, SystemRole::SuperAdmin->value]),
        ];
    }

    private static function pending(Model&PublishableEntity $entity): ?Revision
    {
        return $entity->pendingRevisionOrNull()?->loadMissing('author:id,name');
    }
}
