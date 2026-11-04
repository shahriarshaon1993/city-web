<?php

namespace App\Support\Users;

use App\Models\User;
use App\Support\Auth\SystemRole;
use Spatie\Permission\Models\Role;

/**
 * Business limits on who may change whom (spec/admin.md §10.2 "Users & roles",
 * ADM-AUTH-007, crud.md Users row). Each method returns the reason a change is
 * refused, or null when it is allowed, so the same sentence feeds the Action's
 * error, the disabled-with-reason button (HELP-08) and a bulk summary.
 *
 * These live here and not in the Policy because Gate::before lets a Super Admin
 * pass every Policy; the "last Super Admin" and "not yourself" limits apply to them too.
 */
final class UserGuard
{
    public static function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(SystemRole::SuperAdmin->value);
    }

    /** An Admin manages users, but never a Super Admin. */
    public static function editBlock(User $actor, User $target): ?string
    {
        if (self::isSuperAdmin($target) && ! self::isSuperAdmin($actor)) {
            return (string) __('Only a Super Admin can change a Super Admin.');
        }

        return null;
    }

    public static function deactivateBlock(User $actor, User $target): ?string
    {
        if ($actor->is($target)) {
            return (string) __('You cannot deactivate your own account.');
        }

        return self::editBlock($actor, $target) ?? self::lastSuperAdminBlock($target);
    }

    /**
     * @param  string  $roleName  the role the target would hold afterwards
     */
    public static function roleChangeBlock(User $actor, User $target, string $roleName): ?string
    {
        if ($actor->is($target) && $target->getRoleNames()->first() !== $roleName) {
            return (string) __('You cannot change your own role.');
        }

        return self::assignBlock($actor, $roleName)
            ?? self::editBlock($actor, $target)
            ?? ($roleName === SystemRole::SuperAdmin->value ? null : self::lastSuperAdminBlock($target));
    }

    /** Only a Super Admin hands out the Super Admin role. */
    public static function assignBlock(User $actor, string $roleName): ?string
    {
        return $roleName === SystemRole::SuperAdmin->value && ! self::isSuperAdmin($actor)
            ? (string) __('Only a Super Admin can assign the Super Admin role.')
            : null;
    }

    /**
     * @return list<string> role names the actor may assign
     */
    public static function assignableRoles(User $actor): array
    {
        $names = Role::query()->orderBy('name')->pluck('name')->all();

        return array_values(array_filter($names, fn (string $name): bool => self::assignBlock($actor, $name) === null));
    }

    private static function lastSuperAdminBlock(User $target): ?string
    {
        if (! self::isSuperAdmin($target) || ! $target->is_active) {
            return null;
        }

        $others = User::query()
            ->role(SystemRole::SuperAdmin->value)
            ->where('is_active', true)
            ->whereKeyNot($target->getKey())
            ->exists();

        return $others ? null : (string) __('At least one active Super Admin must remain.');
    }
}
