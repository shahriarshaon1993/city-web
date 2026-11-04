<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\UserInvitation;
use App\Support\Auth\SystemRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * ADM-AUTH-006: invites a user. The account starts with an unusable random
 * password and no verified email; the signed 72-hour link lets the invitee
 * choose a password (AcceptInvitation), which also verifies the email.
 */
final class InviteUser
{
    public const int VALID_HOURS = 72;

    public function handle(string $name, string $email, string|SystemRole $role, ?User $invitedBy = null): User
    {
        $roleName = $role instanceof SystemRole ? $role->value : $role;

        $user = DB::transaction(function () use ($name, $email, $roleName, $invitedBy): User {
            $user = new User;
            $user->forceFill([
                'name' => $name,
                'email' => Str::lower($email),
                'password' => Str::random(64),
                'is_active' => true,
            ])->save();

            $user->assignRole($roleName);

            activity('users')
                ->causedBy($invitedBy)
                ->performedOn($user)
                ->event('invited')
                ->withProperties(['role' => $roleName])
                ->log('User invited');

            return $user;
        });

        $this->sendInvitation($user, $roleName);

        return $user;
    }

    public function sendInvitation(User $user, string $roleName): void
    {
        $url = URL::temporarySignedRoute(
            'invitation.show',
            now()->addHours(self::VALID_HOURS),
            ['user' => $user->getKey()],
        );

        $user->notify(new UserInvitation($url, $roleName, self::VALID_HOURS));
    }
}
