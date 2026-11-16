<?php

namespace App\Actions\Redirects;

use App\Models\Redirect;
use Illuminate\Support\Facades\DB;

/**
 * SEO-TEC-008: hit count and last hit of a rule. A plain query, no model events, so the rule cache
 * is not touched by traffic.
 */
final class RecordRedirectHit
{
    public function handle(int $redirectId): void
    {
        Redirect::query()->whereKey($redirectId)->toBase()->update([
            'hits' => DB::raw('hits + 1'),
            'last_hit_at' => now(),
        ]);
    }
}
