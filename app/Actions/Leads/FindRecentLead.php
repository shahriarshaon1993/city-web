<?php

namespace App\Actions\Leads;

use App\Models\Lead;
use App\Models\Project;

/**
 * FR-LEAD-004: the lead that the same normalised phone opened for the same project (or for none)
 * inside the duplicate window. Shared by the public forms and the admin "New lead" dialog, so both
 * touch the existing lead instead of creating a second one.
 */
final class FindRecentLead
{
    public function handle(string $phone, ?Project $project): ?Lead
    {
        return Lead::query()
            ->where('phone', $phone)
            ->when($project === null, fn ($query) => $query->whereNull('project_id'), fn ($query) => $query->where('project_id', $project?->id))
            ->where('created_at', '>=', now()->subHours(Lead::DUPLICATE_WINDOW_HOURS))
            ->latest('id')
            ->first();
    }
}
