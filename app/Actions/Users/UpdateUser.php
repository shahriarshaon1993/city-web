<?php

namespace App\Actions\Users;

use App\Actions\Activity\LogActivity;
use App\Models\User;
use App\Support\Users\UserGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ADM-SCR-16: edits another user's name, phone and role. The role is the only way to change
 * what someone may do (custom roles come from the Roles tab).
 */
final class UpdateUser
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException when the limits of UserGuard refuse the change
     */
    public function handle(User $actor, User $target, string $name, ?string $phone, string $role): User
    {
        $block = UserGuard::roleChangeBlock($actor, $target, $role);

        if ($block !== null) {
            throw ValidationException::withMessages(['role' => $block]);
        }

        return DB::transaction(function () use ($actor, $target, $name, $phone, $role): User {
            $before = ['name' => $target->name, 'phone' => $target->phone, 'role' => $target->getRoleNames()->first()];

            $target->forceFill(['name' => $name, 'phone' => $phone])->save();
            $target->syncRoles([$role]);

            $after = ['name' => $name, 'phone' => $phone, 'role' => $role];
            $changed = array_keys(array_diff_assoc($after, $before));

            if ($changed !== []) {
                $this->logActivity->handle(
                    'users',
                    'updated',
                    'User updated',
                    $actor,
                    $target,
                    ['old' => array_intersect_key($before, array_flip($changed)), 'attributes' => array_intersect_key($after, array_flip($changed))],
                );
            }

            return $target;
        });
    }
}
