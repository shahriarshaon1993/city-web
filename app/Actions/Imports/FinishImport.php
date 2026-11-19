<?php

namespace App\Actions\Imports;

use App\Actions\Activity\LogActivity;
use App\Actions\Notifications\NotifyImportFinished;
use App\Enums\ImportStatus;
use App\Models\ImportRun;
use Illuminate\Support\Facades\Storage;

/**
 * Closes an import run: the uploaded file is deleted at once (it holds people's data), the outcome
 * goes in the activity log with counts only (ADM-IO-002), and the person who started it is told.
 */
final class FinishImport
{
    public function __construct(
        private readonly LogActivity $logActivity,
        private readonly NotifyImportFinished $notify,
    ) {}

    public function handle(ImportRun $run, ImportStatus $status, ?string $message = null): void
    {
        Storage::disk(ImportRun::DISK)->delete($run->sourcePath());

        $run->forceFill(['status' => $status, 'message' => $message, 'finished_at' => now()])->save();

        $summary = "{$run->created_count} new, {$run->updated_count} updated, {$run->skipped_count} skipped, {$run->failed_count} with a problem";

        $this->logActivity->handle(
            'imports',
            $status === ImportStatus::Finished ? 'finished' : 'failed',
            $status === ImportStatus::Finished
                ? "{$run->type->label()} import finished: {$summary}"
                : "{$run->type->label()} import failed: {$message}",
            $run->user,
            null,
            [
                'type' => $run->type->value,
                'file' => $run->original_name,
                'created' => $run->created_count,
                'updated' => $run->updated_count,
                'skipped' => $run->skipped_count,
                'failed' => $run->failed_count,
            ],
        );

        $this->notify->handle($run);
    }
}
