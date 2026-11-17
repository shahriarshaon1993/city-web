<?php

namespace App\Events;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * FR-LEAD-008 / FR-LEAD-012: a new lead exists (public form or added by hand, `$actor` = the staff
 * member). Dispatched after the commit, so the lead already has its status and owner.
 */
final class LeadCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly Lead $lead, public readonly ?User $actor = null) {}
}
