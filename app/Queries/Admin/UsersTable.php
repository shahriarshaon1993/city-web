<?php

namespace App\Queries\Admin;

use App\Http\Resources\Admin\UserRowResource;
use App\Models\User;
use App\Queries\Admin\Concerns\FiltersByDate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Role;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * ADM-SCR-16 users list. Status is derived: inactive (deactivated), invited (invite not yet
 * accepted: no verified email and never signed in) or active.
 *
 * @extends AdminTable<User>
 */
final class UsersTable extends AdminTable
{
    use FiltersByDate;

    public const string KEY = 'users';

    public function key(): string
    {
        return self::KEY;
    }

    public function permissions(): array
    {
        return ['users.manage'];
    }

    public function url(): string
    {
        return route('admin.users.index');
    }

    protected function baseQuery(User $actor): Builder
    {
        return User::query()->with('roles');
    }

    protected function filters(): array
    {
        return [
            AllowedFilter::callback('role', fn (Builder $query, mixed $value) => $query->whereHas(
                'roles',
                fn (Builder $roles) => $roles->whereIn('name', Arr::wrap($value)),
            )),
            AllowedFilter::callback('status', function (Builder $query, mixed $value): void {
                $statuses = Arr::wrap($value);

                $query->where(function (Builder $query) use ($statuses): void {
                    foreach ($statuses as $status) {
                        match ($status) {
                            'active' => $query->orWhere(fn (Builder $q) => $this->active($q)),
                            'invited' => $query->orWhere(fn (Builder $q) => $this->invited($q)),
                            'inactive' => $query->orWhere('is_active', false),
                            default => null,
                        };
                    }
                });
            }),
            ...$this->dateRange('last_login', 'last_login_at'),
            ...$this->dateRange('created', 'created_at'),
        ];
    }

    protected function sorts(): array
    {
        return ['name', 'email', 'is_active', 'last_login_at', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
    }

    protected function resource(): string
    {
        return UserRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Name', 'value' => fn (User $user): string => $user->name],
            'email' => ['label' => 'Email', 'value' => fn (User $user): string => $user->email],
            'phone' => ['label' => 'Phone', 'value' => fn (User $user): ?string => $user->phone],
            'role' => ['label' => 'Role', 'value' => fn (User $user): string => $user->getRoleNames()->implode(', ')],
            'status' => ['label' => 'Status', 'value' => fn (User $user): string => UserRowResource::statusOf($user)],
            'last_login_at' => ['label' => 'Last sign-in', 'value' => fn (User $user): ?string => $user->last_login_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
            'created_at' => ['label' => 'Added', 'value' => fn (User $user): ?string => $user->created_at?->timezone('Asia/Dhaka')->format('d M Y H:i')],
        ];
    }

    public function filterOptions(User $actor): array
    {
        $count = fn (callable $scope): int => $scope(User::query())->count();

        return [
            'role' => Role::query()->withCount('users')->orderBy('name')->get()
                ->map(fn (Role $role): array => ['value' => $role->name, 'label' => $role->name, 'count' => (int) $role->users_count])
                ->all(),
            'status' => [
                ['value' => 'active', 'label' => 'Active', 'count' => $count(fn (Builder $q) => $this->active($q))],
                ['value' => 'invited', 'label' => 'Invited', 'count' => $count(fn (Builder $q) => $this->invited($q))],
                ['value' => 'inactive', 'label' => 'Inactive', 'count' => $count(fn (Builder $q) => $q->where('is_active', false))],
            ],
        ];
    }

    /**
     * @template TUser of Model
     *
     * @param  Builder<TUser>  $query
     * @return Builder<TUser>
     */
    private function active(Builder $query): Builder
    {
        return $query->where('is_active', true)->where(
            fn (Builder $q) => $q->whereNotNull('email_verified_at')->orWhereNotNull('last_login_at'),
        );
    }

    /**
     * @template TUser of Model
     *
     * @param  Builder<TUser>  $query
     * @return Builder<TUser>
     */
    private function invited(Builder $query): Builder
    {
        return $query->where('is_active', true)->whereNull('email_verified_at')->whereNull('last_login_at');
    }
}
