<?php

namespace App\Actions\Notifications;

use App\Models\User;

/**
 * DEL-09: notifications are hard-deleted (no Recycle bin) — one of the user's own, or all the ones
 * they have read. Returns how many were removed.
 */
final class DeleteNotifications
{
    public function handle(User $user, ?string $id = null): int
    {
        return $user->notifications()
            ->when($id !== null, fn ($query) => $query->whereKey($id), fn ($query) => $query->whereNotNull('read_at'))
            ->delete();
    }
}
