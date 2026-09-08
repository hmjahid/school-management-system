<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily database backup at 02:00 server time
Schedule::command('backup:database')
    ->dailyAt('02:00')
    ->withoutOverlapping();

// Monitor queue failures every 5 minutes (alerts via the default log channel / Slack)
Schedule::command('queue:monitor-failed')
    ->everyFiveMinutes()
    ->withoutOverlapping();

// Process due recurring payments daily
Schedule::command('payments:process-recurring')
    ->dailyAt('01:00')
    ->withoutOverlapping();

// Process scheduled notifications every 5 minutes (with --force so it runs in all envs)
Schedule::command('notifications:process-scheduled --force')
    ->everyFiveMinutes()
    ->withoutOverlapping();
