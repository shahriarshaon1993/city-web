<?php

namespace App\Support\Activity;

use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * How an activity row's action reads in the log (CMP-ADM-STATUS): a label ("Signed in") and a
 * badge tone (success / info / warning / error / neutral). Known events are listed; anything
 * else is the event name in plain words, with the tone taken from its verb.
 */
final class ActivityAction
{
    /**
     * @var array<string, array{string, string}> `{log}.{event}` → label, tone
     */
    private const array KNOWN = [
        'auth.login' => ['Signed in', 'success'],
        'auth.logout' => ['Signed out', 'neutral'],
        'auth.login_failed' => ['Sign-in failed', 'warning'],
        'auth.lockout' => ['Locked out', 'error'],
        'auth.password_changed' => ['Password changed', 'info'],
        'users.invited' => ['Invited', 'info'],
        'users.invitation_accepted' => ['Joined', 'success'],
        'users.invitation_resent' => ['Invite resent', 'info'],
        'users.deactivated' => ['Deactivated', 'warning'],
        'users.activated' => ['Activated', 'success'],
        'export.exported' => ['Exported', 'neutral'],
    ];

    /**
     * @return array{label: string, tone: string}
     */
    public static function for(Activity $activity): array
    {
        $event = (string) ($activity->event ?? '');
        $known = self::KNOWN["{$activity->log_name}.{$event}"] ?? null;

        if ($known !== null) {
            return ['label' => $known[0], 'tone' => $known[1]];
        }

        $label = $event !== '' ? Str::headline($event) : 'Activity';

        return ['label' => $label, 'tone' => self::toneOf($event)];
    }

    private static function toneOf(string $event): string
    {
        return match (true) {
            str_contains($event, 'delet'), str_contains($event, 'fail') => 'error',
            str_contains($event, 'creat'), str_contains($event, 'publish'), str_contains($event, 'restor') => 'success',
            str_contains($event, 'updat'), str_contains($event, 'chang') => 'info',
            default => 'neutral',
        };
    }
}
