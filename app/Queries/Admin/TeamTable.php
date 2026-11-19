<?php

namespace App\Queries\Admin;

use App\Enums\TeamGroup;
use App\Http\Resources\Admin\TeamMemberRowResource;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * ADM-SCR-09 team members: grouped like the team grids (leadership, management, team), in drag order.
 *
 * @extends SocialTable<TeamMember>
 */
final class TeamTable extends SocialTable
{
    public const string KEY = 'team';

    public function key(): string
    {
        return self::KEY;
    }

    public function url(): string
    {
        return route('admin.team.index');
    }

    protected function model(): string
    {
        return TeamMember::class;
    }

    protected function baseQuery(User $actor): Builder
    {
        return TeamMember::query()->with('photo.media');
    }

    protected function filters(): array
    {
        return [$this->statusFilter(), $this->enumFilter('group', 'group', TeamGroup::class)];
    }

    protected function sorts(): array
    {
        return ['sort_order', 'name', 'role_title', 'group', 'is_published'];
    }

    protected function applySearch(Builder $query, string $term): void
    {
        $like = $this->likeTerm($term);

        $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('role_title', 'like', $like));
    }

    protected function resource(): string
    {
        return TeamMemberRowResource::class;
    }

    public function exportColumns(): array
    {
        return [
            'name' => ['label' => 'Name', 'value' => fn (TeamMember $member): string => $member->name],
            'role_title' => ['label' => 'Role', 'value' => fn (TeamMember $member): string => $member->role_title],
            'group' => ['label' => 'Group', 'value' => fn (TeamMember $member): string => $member->group->label()],
            'is_published' => ['label' => 'Published', 'value' => fn (TeamMember $member): string => $member->is_published ? 'Yes' : 'No'],
        ];
    }

    public function filterOptions(User $actor): array
    {
        return ['status' => $this->statusOptions(), 'group' => $this->enumOptions('group', TeamGroup::class)];
    }
}
