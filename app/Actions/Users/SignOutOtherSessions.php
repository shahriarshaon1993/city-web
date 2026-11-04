<?php

namespace App\Actions\Users;

use App\Actions\Auth\RecordAuthEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * ADM-AUTH-005 "Sign out other sessions": with the database session driver
 * the other devices are signed out by deleting their rows, so the password
 * hash stays untouched.
 */
final class SignOutOtherSessions
{
    public function __construct(private readonly RecordAuthEvent $record) {}

    /**
     * @return int how many sessions were ended
     */
    public function handle(User $user, string $currentSessionId): int
    {
        $ended = DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->where('id', '!=', $currentSessionId)
            ->delete();

        if ($ended > 0) {
            $this->record->handle('sessions_ended', 'Signed out other sessions', $user, properties: ['count' => $ended]);
        }

        return $ended;
    }
}
