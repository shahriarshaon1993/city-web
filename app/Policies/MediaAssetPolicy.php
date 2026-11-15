<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

/**
 * Media (spec/admin.md §10.2): view / upload / edit / delete. A Content Editor deletes unused files
 * only (`media.delete-unused`); the usage guard itself is `MediaAsset::binUsageBlock` (CMS-MED-008),
 * which refuses for everyone.
 */
class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['media.view', 'media.upload', 'media.update']);
    }

    public function create(User $user): bool
    {
        return $user->can('media.upload');
    }

    public function update(User $user, MediaAsset $asset): bool
    {
        return $user->can('media.update');
    }

    public function delete(User $user, MediaAsset $asset): bool
    {
        return $user->canAny(['media.delete', 'media.delete-unused']);
    }
}
