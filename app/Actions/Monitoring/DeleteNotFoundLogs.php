<?php

namespace App\Actions\Monitoring;

use App\Actions\Activity\LogActivity;
use App\Models\NotFoundLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * DEL-09: 404 log rows are deleted for good (no Recycle bin); the address comes back if a visitor
 * asks for it again.
 */
final class DeleteNotFoundLogs
{
    public function __construct(private readonly LogActivity $logActivity) {}

    /**
     * @param  Collection<int, NotFoundLog>  $logs
     */
    public function handle(User $actor, Collection $logs): int
    {
        if ($logs->isEmpty()) {
            return 0;
        }

        NotFoundLog::query()->whereKey($logs->pluck('id')->all())->delete();

        $this->logActivity->handle('seo', 'deleted', $logs->count().' 404 address(es) deleted', $actor, null, [
            'paths' => $logs->pluck('path')->take(10)->values()->all(),
        ]);

        return $logs->count();
    }
}
