<?php

namespace App\Notifications;

use App\Models\ExportRun;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

/**
 * §10.8 "export ready": the file of a large table export waits behind a signed link that works for
 * 24 hours (ADM-TBL-012). The link is a path on this site, so the inbox can open it.
 */
class ExportReadyNotification extends Notification
{
    public function __construct(public readonly ExportRun $run) {}

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
        $failed = $run->status === 'failed';

        return [
            'kind' => 'export_ready',
            'title' => $failed ? 'Export failed' : 'Your export is ready',
            'body' => $failed
                ? ($run->message ?? 'The export stopped before it finished.')
                : number_format((int) $run->row_count).' rows · link works for '.ExportRun::LINK_HOURS.' hours',
            'url' => $failed ? route('admin.import.index', absolute: false) : URL::temporarySignedRoute(
                'admin.exports.download',
                now()->addHours(ExportRun::LINK_HOURS),
                ['export' => $run->id],
                absolute: false,
            ),
            'export_run_id' => $run->id,
        ];
    }
}
