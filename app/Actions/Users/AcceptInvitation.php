<?php

namespace App\Actions\Users;

use App\Models\User;

/**
 * Completes an invitation: sets the chosen password and verifies the email.
 * The link is single-use because it only works while the email is unverified.
 */
final class AcceptInvitation
{
    public function handle(User $user, string $password): void
    {
        $user->forceFill([
            'password' => $password,
            'email_verified_at' => now(),
        ])->save();

        activity('users')
            ->causedBy($user)
            ->performedOn($user)
            ->event('invitation_accepted')
            ->withProperties(['ip' => request()->ip()])
            ->log('Invitation accepted');
    }
}
