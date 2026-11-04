<?php

namespace App\Actions\Users;

use App\Models\User;

/**
 * Own name, email and phone from the profile (ADM-SCR-20). A changed email
 * must be confirmed again, so the verification mail goes out right away.
 */
final class UpdateProfile
{
    /**
     * @return bool whether the email changed (and needs confirming)
     */
    public function handle(User $user, string $name, string $email, ?string $phone): bool
    {
        $user->fill(['name' => $name, 'email' => $email, 'phone' => $phone]);

        $emailChanged = $user->isDirty('email');

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return $emailChanged;
    }
}
