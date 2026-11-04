<?php

namespace App\Support\Auth;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in devices of a user for the profile (ADM-SCR-20), read from the
 * `sessions` table. The user agent is reduced to "Browser on System" with a
 * few patterns; no package is needed for a label.
 */
final class UserSessions
{
    /**
     * @return array<int, array{id: string, device: string, ip: string|null, last_active: string, current: bool}>
     */
    public static function for(User $user, string $currentSessionId): array
    {
        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $row): array => [
                'id' => hash('sha256', $row->id),
                'device' => self::device((string) $row->user_agent),
                'ip' => is_string($row->ip_address) ? $row->ip_address : null,
                'last_active' => Carbon::createFromTimestamp($row->last_activity)->toIso8601String(),
                'current' => $row->id === $currentSessionId,
            ])
            ->values()
            ->all();
    }

    public static function device(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $system = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return $system === null ? $browser : "{$browser} on {$system}";
    }
}
