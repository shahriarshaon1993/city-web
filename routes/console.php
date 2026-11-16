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
