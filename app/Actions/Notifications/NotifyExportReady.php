<?php

namespace App\Actions\Notifications;

use App\Models\ExportRun;
use App\Notifications\ExportReadyNotification;

/** Tells the person who asked for a large export that the file is ready (or why it is not). */
final class NotifyExportReady
{
    public function handle(ExportRun $run): void
    {
        $run->user?->notify(new ExportReadyNotification($run));
    }
}
