<?php

namespace App\Enums;

/** DM-LEADH `kind`. */
enum LeadActivityKind: string
{
    case Submission = 'submission';
    case Touch = 'touch';
    case Status = 'status';
    case Assignment = 'assignment';
    case Note = 'note';
    case FollowUp = 'follow_up';
    case Email = 'email';
}
