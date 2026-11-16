<?php

namespace App\Actions\Monitoring;

use App\Actions\Activity\LogActivity;
use App\Models\NotFoundLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The inline "Ignore" of the 404 monitor (crud.md: 404 log rows). An ignored address stays in the
 * list under "Ignored" and is out of the default view.
 */
final class SetNotFoundIgnored
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  Collection<int, NotFoundLog>  $logs
     * @return int how many rows changed
     */
    public function handle(User $actor, Collection $logs, bool $ignored): int
    {
        $changing = $logs->filter(fn (NotFoundLog $log): bool => $log->is_ignored !== $ignored);

        if ($changing->isEmpty()) {
            return 0;
        }

        NotFoundLog::query()->whereKey($changing->pluck('id')->all())->update(['is_ignored' => $ignored]);

        $this->logActivity->handle('seo', 'updated', $changing->count().' 404 address(es) '.($ignored ? 'ignored' : 'un-ignored'), $actor, null, [
            'paths' => $changing->pluck('path')->take(10)->values()->all(),
        ]);

        return $changing->count();
    }
}
