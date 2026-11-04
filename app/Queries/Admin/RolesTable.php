<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\RoleRowResource;
use App\Models\User;
use App\Support\Auth\SystemRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-16 roles list: the six system roles (locked) and the custom roles a Super Admin adds.
 *
 * @extends AdminTable<Role>
 */
final class RolesTable extends AdminTable
{
    public const string KEY = 'roles';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['roles.manage'];
    }

    public function url(): string
    {
        return route('admin.roles.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return Role::query()->withCount(['users', 'permissions']);
    }

    protected function filters(): array
    {
        return [
            AllowedFilter::callback('type', function (Builder $query, mixed $value): void {
                $types = Arr::wrap($value);

                if (in_array('system', $types, true) && ! in_array('custom', $types, true)) {
                    $query->whereIn('name', SystemRole::names());
                } elseif (in_array('custom', $types, true) && ! in_array('system', $types, true)) {
                    $query->whereNotIn('name', SystemRole::names());
                }
            }),
            AllowedFilter::callback('users_min', fn (Builder $query, mixed $value) => is_numeric($value)
                ? $query->has('users', '>=', (int) $value)
                : null),
            AllowedFilter::callback('users_max', fn (Builder $query, mixed $value) => is_numeric($value)
                ? $query->has('users', '<=', (int) $value)
                : null),
        ];
    }

    protected function sorts(): array
    {
        return ['name', 'users_count', 'permissions_count', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');
    }

    protected function resource(): string
    {
        return RoleRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Role', 'value' => fn (Role $role): string => $role->name],
            'type' => ['label' => 'Type', 'value' => fn (Role $role): string => RoleRowResource::typeOf($role)],
            'users_count' => ['label' => 'Users', 'value' => fn (Role $role): int => (int) $role->users_count],
            'permissions_count' => ['label' => 'Permissions', 'value' => fn (Role $role): int => (int) $role->permissions_count],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $system = Role::query()->whereIn('name', SystemRole::names())->count();

        return [
            'type' => [
                ['value' => 'system', 'label' => 'System', 'count' => $system],
                ['value' => 'custom', 'label' => 'Custom', 'count' => Role::query()->count() - $system],
            ],
        ];
    }
}
