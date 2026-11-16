<?php

namespace App\Actions\Monitoring;

use App\Models\NotFoundLog;
use App\Support\Redirects\NotFoundIgnore;
use App\Support\Redirects\RedirectPath;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * SEO-TEC-009 (`TrackNotFound`): counts one request for an address that does not exist. Addresses
 * on the ignore list are not written. Called after the response is sent, so a visitor never waits.
 */
final class RecordNotFound
{
    public function handle(string $path, ?string $referrer): void
    {
        $path = Str::limit(RedirectPath::normalise($path), 500, '');

        if (NotFoundIgnore::matches($path)) {
            return;
        }

        $referrer = $referrer === null || $referrer === '' ? null : Str::limit($referrer, 500, '');
        $now = now();

        $updated = NotFoundLog::query()->where('path', $path)->toBase()->update([
            'hits' => DB::raw('hits + 1'),
            'last_seen_at' => $now,
            'referrer' => $referrer ?? DB::raw('referrer'),
        ]);

        if ($updated === 0) {
            NotFoundLog::query()->toBase()->insertOrIgnore([
                'path' => $path,
                'referrer' => $referrer,
                'hits' => 1,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'is_ignored' => false,
            ]);
        }
    }
}
