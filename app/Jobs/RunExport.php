<?php

namespace App\Jobs;

use App\Actions\Tables\GenerateExport;
use App\Models\ExportRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * ADM-TBL-012 queued export on the `exports` queue (ARC-011). Writing the file again is harmless, so
 * it may retry with the standard back-off; `GenerateExport` reports its own failures on the run.
 */
class RunExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public int $runId)
    {
        $this->onQueue('exports');
    }

    public function handle(GenerateExport $generate): void
    {
        $run = ExportRun::query()->find($this->runId);

        if ($run === null || ! in_array($run->status, ['queued', 'running'], true)) {
            return;
        }

        $generate->handle($run);
    }

    public function failed(Throwable $exception): void
    {
        $run = ExportRun::query()->find($this->runId);

        if ($run !== null && in_array($run->status, ['queued', 'running'], true)) {
            $run->forceFill(['status' => 'failed', 'message' => 'The export stopped unexpectedly. Try it again.'])->save();
        }
    }
}
