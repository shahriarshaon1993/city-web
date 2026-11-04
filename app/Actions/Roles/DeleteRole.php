<?php

namespace App\Actions\Roles;

use App\Actions\Activity\LogActivity;
use App\Models\User;
use App\Support\Auth\SystemRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * crud.md Roles row: hard delete with a guard. A system role, or a role that still has users,
 * stays (DEL-03: guards instead of orphans).
 */
final class DeleteRole
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $actor, Role $role): void
    {
        $users = $role->users()->count();

        $block = match (true) {
            in_array($role->name, SystemRole::names(), true) => __('System roles cannot be deleted.'),
            $users > 0 => trans_choice('{1} 1 user has this role. Give them another role first.|[2,*] :count users have this role. Give them another role first.', $users),
            default => null,
        };

        if ($block !== null) {
            throw ValidationException::withMessages(['role' => $block]);
        }

        DB::transaction(function () use ($actor, $role): void {
            $role->delete();

            $this->logActivity->handle('roles', 'deleted', 'Role deleted', $actor, null, ['role' => $role->name]);
        });
    }
}
