<?php

namespace App\Actions\Users;

use App\Actions\Activity\LogActivity;
use App\Http\Resources\Admin\UserRowResource;
use App\Models\User;
use App\Support\Users\UserGuard;
use Illuminate\Validation\ValidationException;

/**
 * ADM-AUTH-006: sends a fresh 72-hour invite link to someone who has not accepted yet.
 */
final class ResendInvitation
{
    public function __construct(
        private readonly InviteUser $inviteUser,
        private readonly LogActivity $logActivity,
    ) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, User $target): void
    {
        $block = UserGuard::editBlock($actor, $target);

        if ($block === null && UserRowResource::statusOf($target) !== 'invited') {
            $block = __(':name has already accepted the invitation.', ['name' => $target->name]);
        }

        if ($block !== null) {
            throw ValidationException::withMessages(['user' => $block]);
        }

        $this->inviteUser->sendInvitation($target, (string) $target->getRoleNames()->first());

        $this->logActivity->handle('users', 'invitation_resent', 'Invitation resent', $actor, $target);
    }
}
