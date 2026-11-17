<?php

namespace App\Queries\Admin;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * FR-LEAD-007: the people a lead can be handed to — active users who may work on leads assigned to
 * them (`leads.view-assigned`: Sales Executives, Sales Managers, Admins).
 */
final class LeadAssignees
{
    /**
     * @return Collection<int, User>
     */
    public function get(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->permission('leads.view-assigned')
            ->orderBy('name')
            ->get(['users.id', 'users.name']);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function options(): array
    {
        return $this->get()->map(fn (User $user): array => ['id' => $user->id, 'name' => $user->name])->values()->all();
    }
}
