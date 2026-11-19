<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ADM-LOG-004: 365-day retention (activitylog.delete_records_older_than_days); the host cron runs schedule:run (20.1).
Schedule::command('activitylog:clean')->dailyAt('02:30');

// SEO-TEC-009: 404 monitor entries not seen for 90 days.
Schedule::command('not-found:purge')->dailyAt('03:15');

// DEL-06: nightly auto-delete of Recycle bin items past their date.
Schedule::command('recycle-bin:purge')->dailyAt('03:00');

// ARC-012 / CMS-PUB-003: every minute publish what is due (scheduled items, pending revisions), then
// run the queue worker for 50 s (media conversions, later notifications and snapshots). Shared hosting
// has no daemon, so the host cron's `schedule:run` is the only process (20.1).
Schedule::command('publishing:run')->everyMinute()->withoutOverlapping();
Schedule::command('queue:work --stop-when-empty --max-time=50 --queue=default,notifications,media,snapshots,exports')
    ->everyMinute()
    ->withoutOverlapping();

// NFR-PRIV-04: lead IPs older than 90 days become hashes.
Schedule::command('leads:hash-ips')->dailyAt('03:30');

// §10.8: one "follow-ups due today" digest per assignee at 09:00 Dhaka time (the host cron runs every minute).
Schedule::command('leads:notify-follow-ups')->dailyAt('09:00')->timezone('Asia/Dhaka');

// FR-LEAD-012: the webhook delivery log is kept for 30 days.
Schedule::command('webhooks:prune')->dailyAt('03:45');

// FR-CAR-003 / ARC-012: an open job whose deadline has passed closes at 00:05 Asia/Dhaka.
Schedule::command('careers:close-expired')->dailyAt('00:05')->timezone('Asia/Dhaka');

// NFR-PRIV-04: applications not updated for 12 months go, with their CVs.
Schedule::command('applications:purge')->dailyAt('03:20');

// ADM-IO-001 / ADM-TBL-012: export files whose 24-hour link expired, unconfirmed import uploads and old
// error files leave the private disk.
Schedule::command('transfers:purge')->dailyAt('03:50');
