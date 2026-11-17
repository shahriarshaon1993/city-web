<?php

namespace App\Enums;

/** What changed on a lead, carried by `LeadUpdated` (webhook `change`, FR-LEAD-012). */
enum LeadChange: string
{
    case Touched = 'touched';
    case Status = 'status';
    case Assigned = 'assigned';
    case FollowUp = 'follow_up';
    case Note = 'note';
}
