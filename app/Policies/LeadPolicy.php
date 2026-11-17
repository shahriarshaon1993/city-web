<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/**
 * Leads (spec/leads.md permissions, spec/admin.md §10.2, AC-LEAD-05): `leads.view-all` (Admin, Sales
 * Manager, Viewer) sees every lead, `leads.view-assigned` (Sales Executive) only the ones assigned to
 * them. Changing a lead needs `leads.update` on top of seeing it; Delete needs `leads.delete` (Admin).
 * Phone numbers and emails need `leads.view-contact`, otherwise they are masked by the Resources.
 */
class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAny(['leads.view-all', 'leads.view-assigned']);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->can('leads.view-all')
            || ($user->can('leads.view-assigned') && $lead->assigned_to === $user->id);
    }

    /** DEC-33: "New lead" for phone and walk-in enquiries. */
    public function create(User $user): bool
    {
        return $user->can('leads.update') && $this->viewAny($user);
    }

    /** Status, notes and follow-up. */
    public function update(User $user, Lead $lead): bool
    {
        return $user->can('leads.update') && $this->view($user, $lead);
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->can('leads.assign') && $this->view($user, $lead);
    }

    public function export(User $user): bool
    {
        return $user->can('leads.export') && $user->can('leads.view-all');
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->can('leads.delete') && $this->view($user, $lead);
    }

    public function viewContact(User $user): bool
    {
        return $user->can('leads.view-contact');
    }
}
