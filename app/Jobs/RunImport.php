<?php

namespace App\Jobs;

use App\Actions\Imports\FinishImport;
use App\Actions\Imports\ProcessImportSlice;
use App\Enums\ImportStatus;
use App\Models\ImportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * ADM-IO-001 queued import. One job runs one slice (about 40 s of rows) and queues the next, so no
 * single job outlives a shared host's worker limit. It does not retry: rows that were saved are never
 * written twice, and a crash is reported on the run instead of repeating it.
 */
class RunImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $runId)
    {
        $this->onQueue('default');
    }

    public function handle(ProcessImportSlice $slice): void
    {
        $run = ImportRun::query()->find($this->runId);

        if ($run === null || ! $run->status->isWorking()) {
            return;
        }

        if ($run->status === ImportStatus::Queued) {
            $run->forceFill(['status' => ImportStatus::Running, 'started_at' => now()])->save();
        }

        if (! $slice->handle($run)) {
            self::dispatch($run->id)->delay(now()->addSeconds(2));
        }
    }

    public function failed(Throwable $exception): void
    {
        $run = ImportRun::query()->find($this->runId);

        if ($run !== null && $run->status->isWorking()) {
            app(FinishImport::class)->handle($run, ImportStatus::Failed, 'The import stopped unexpectedly. Rows before the problem were saved.');
        }
    }
}
