<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/**
 * Leads (spec/leads.md permissions, AC-LEAD-05): `leads.view-all` (Admin, Sales Manager) sees every
 * lead, `leads.view-assigned` (Sales Executive) only the ones assigned to them. 15.2 adds the
 * update, assign, export and delete rules here.
 */
class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        return $user->can('leads.view-all')
            || ($user->can('leads.view-assigned') && $lead->assigned_to === $user->id);
    }
}
