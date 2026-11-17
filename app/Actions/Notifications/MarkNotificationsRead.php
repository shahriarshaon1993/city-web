<?php

namespace App\Actions\Notifications;

use App\Models\User;

/**
 * ADM-SCR-21: marks one notification of the user, or all of theirs, as read. Returns how many
 * changed. Another person's notification is never reachable: the query starts from the user.
 */
final class MarkNotificationsRead
{
    public function handle(User $user, ?string $id = null): int
    {
        return $user->unreadNotifications()
            ->when($id !== null, fn ($query) => $query->whereKey($id))
            ->update(['read_at' => now()]);
    }
}
