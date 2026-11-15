<?php

namespace App\Support\RecycleBin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Defaults for `Trashable`: 90 days retention, no thumbnail, no guard, no restore conflicts.
 * An entity overrides only what differs (DEL-06: leads, applications, subscribers 365 days,
 * media 30 days, users never).
 */
trait BinBehaviour
{
    /**
     * @return Builder<covariant Model>
     */
    public static function binTrashedQuery(): Builder
    {
        return static::onlyTrashed();
    }

    public static function binRetentionDays(): ?int
    {
        return 90;
    }

    public function binThumbnail(): ?string
    {
        return null;
    }

    public function binUrl(): ?string
    {
        return null;
    }

    public function binUsageBlock(User $actor): ?string
    {
        return null;
    }

    /**
     * @return list<RestoreConflict>
     */
    public function binRestoreConflicts(): array
    {
        return [];
    }

    /**
     * @param  array<string, mixed>  $resolution
     */
    public function binResolveConflicts(array $resolution): void {}

    public function binTrashed(): void {}

    public function binForceDeleting(): void {}
}
