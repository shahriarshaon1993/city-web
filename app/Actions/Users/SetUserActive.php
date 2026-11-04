<?php

namespace App\Actions\Users;

use App\Actions\Activity\LogActivity;
use App\Models\User;
use App\Support\Users\UserGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ADM-AUTH-007: deactivates or re-activates a user. A deactivated user cannot sign in and every
 * session they hold ends. Reassigning their open leads (FR-LEAD-007) joins this Action with the
 * leads module in 15.1.
 */
final class SetUserActive
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @return bool whether anything changed
     *
     * @throws ValidationException when the limits of UserGuard refuse the change
     */
    public function handle(User $actor, User $target, bool $active): bool
    {
        $block = $active ? UserGuard::editBlock($actor, $target) : UserGuard::deactivateBlock($actor, $target);

        if ($block !== null) {
            throw ValidationException::withMessages(['user' => $block]);
        }

        if ($target->is_active === $active) {
            return false;
        }

        DB::transaction(function () use ($actor, $target, $active): void {
            $target->forceFill(['is_active' => $active])->save();

            if (! $active) {
                DB::table('sessions')->where('user_id', $target->getKey())->delete();
            }

            $this->logActivity->handle('users', $active ? 'activated' : 'deactivated', $active ? 'User activated' : 'User deactivated', $actor, $target);
        });

        return true;
    }
}
