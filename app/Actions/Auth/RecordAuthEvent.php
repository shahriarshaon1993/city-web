<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * ADM-LOG-002: one activity-log row for an authentication event, with the
 * IP address and user agent. Credentials are never stored.
 */
final class RecordAuthEvent
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function handle(string $event, string $description, ?User $user, ?Request $request = null, array $properties = []): void
    {
        $request ??= request();

        activity('auth')
            ->causedBy($user)
            ->event($event)
            ->withProperties([
                ...$properties,
                'ip' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ])
            ->log($description);
    }
}
