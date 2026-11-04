<?php

namespace App\Http\Resources\Admin;

use App\Support\Auth\SystemRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Role;

/**
 * One row of the roles table. System roles are locked (crud.md Roles row); a custom role
 * with users assigned cannot be deleted until they have another role (DEL-03 guard).
 *
 * @mixin Role
 */
class RoleRowResource extends JsonResource
{
    public static function typeOf(Role $role): string
    {
        return in_array($role->name, SystemRole::names(), true) ? 'system' : 'custom';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $type = self::typeOf($this->resource);
        $users = (int) $this->users_count;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $type,
            'users_count' => $users,
            'permissions_count' => (int) $this->permissions_count,
            'created_at' => $this->created_at?->toIso8601String(),
            'can' => [
                'edit' => $type === 'custom',
                'delete' => $type === 'custom' && $users === 0,
            ],
            'blocked' => [
                'edit' => $type === 'system' ? __('System roles are fixed. Create a custom role to choose your own permissions.') : null,
                'delete' => match (true) {
                    $type === 'system' => __('System roles cannot be deleted.'),
                    $users > 0 => trans_choice('{1} 1 user has this role. Give them another role first.|[2,*] :count users have this role. Give them another role first.', $users),
                    default => null,
                },
            ],
        ];
    }
}
