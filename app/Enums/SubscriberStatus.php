<?php

namespace App\Enums;

/** DM-SUBS `status`. */
enum SubscriberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Unsubscribed = 'unsubscribed';
}
