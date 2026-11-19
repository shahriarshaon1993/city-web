<?php

namespace App\Notifications;

use App\Models\ImportRun;
use Illuminate\Notifications\Notification;

/**
 * §10.8 "import finished": tells whoever started the import. Counts only, no names or numbers.
 */
class ImportFinishedNotification extends Notification
{
    public function __construct(public readonly ImportRun $run) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $run = $this->run;
        $failed = $run->status->value === 'failed';

        return [
            'kind' => 'import_finished',
            'title' => $failed ? "{$run->type->label()} import failed" : "{$run->type->label()} import finished",
            'body' => $failed
                ? ($run->message ?? 'The import stopped before it finished.')
                : "{$run->created_count} new, {$run->updated_count} updated, {$run->skipped_count} skipped, {$run->failed_count} with a problem",
            'url' => route('admin.import.runs.show', $run, false),
            'import_run_id' => $run->id,
        ];
    }
}
