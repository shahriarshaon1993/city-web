<?php

namespace App\Events;

use App\Enums\LeadChange;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * FR-LEAD-012: an existing lead changed (repeat enquiry, status, owner, follow-up, note). `$actor`
 * is the staff member; null for a visitor's repeat enquiry.
 */
final class LeadUpdated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly Lead $lead, public readonly LeadChange $change, public readonly ?User $actor = null) {}
}
