<?php

namespace App\Actions\Notifications;

use App\Models\ImportRun;
use App\Notifications\ImportFinishedNotification;

/** Tells the person who started an import that it is over. */
final class NotifyImportFinished
{
    public function handle(ImportRun $run): void
    {
        $run->user?->notify(new ImportFinishedNotification($run));
    }
}
