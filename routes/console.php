<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ADM-LOG-004: 365-day retention (activitylog.delete_records_older_than_days); the host cron runs schedule:run (20.1).
Schedule::command('activitylog:clean')->dailyAt('02:30');
