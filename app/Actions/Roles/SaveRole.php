<?php

namespace App\Actions\Roles;

use App\Actions\Activity\LogActivity;
use App\Models\User;
use App\Support\Auth\SystemRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

/**
 * ADM-SCR-16 / crud.md Roles: creates or updates a custom role and its permissions. The six
 * system roles are fixed (SyncSystemRoles owns them). `dashboard.view` is always included,
 * because /admin is the sign-in landing page (10.2).
 */
final class SaveRole
{
    public const string ALWAYS = 'dashboard.view';

    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  list<string>  $permissions  names from PermissionMatrix
     *
     * @throws ValidationException when the role is a system role
     */
    public function handle(User $actor, ?Role $role, string $name, array $permissions): Role
    {
        if ($role !== null && in_array($role->name, SystemRole::names(), true)) {
            throw ValidationException::withMessages(['name' => __('System roles are fixed.')]);
        }

        $permissions = array_values(array_unique([self::ALWAYS, ...$permissions]));

        return DB::transaction(function () use ($actor, $role, $name, $permissions): Role {
            $isNew = $role === null;
            $before = $isNew ? [] : $role->permissions->pluck('name')->all();
            $oldName = $role?->name;

            $role ??= new Role(['guard_name' => 'web']);
            $role->name = $name;
            $role->save();
            $role->syncPermissions($permissions);

            $this->logActivity->handle(
                'roles',
                $isNew ? 'created' : 'updated',
                $isNew ? 'Role created' : 'Role updated',
                $actor,
                null,
                [
                    'role' => $name,
                    ...($oldName !== null && $oldName !== $name ? ['old' => ['name' => $oldName], 'attributes' => ['name' => $name]] : []),
                    'permissions_added' => implode(', ', array_diff($permissions, $before)),
                    'permissions_removed' => implode(', ', array_diff($before, $permissions)),
                ],
            );

            return $role;
        });
    }
}
