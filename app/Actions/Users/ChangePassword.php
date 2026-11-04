<?php

namespace App\Actions\Users;

use App\Actions\Auth\RecordAuthEvent;
use App\Models\User;

/**
 * Own-password change from the profile (ADM-LOG-002 "password changed").
 */
final class ChangePassword
{
    public function __construct(private readonly RecordAuthEvent $record) {}

    public function handle(User $user, string $password): void
    {
        $user->update(['password' => $password]);

        $this->record->handle('password_changed', 'Password changed', $user);
    }
}
