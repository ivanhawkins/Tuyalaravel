<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Sync unlock logs every 15 minutes
Schedule::job(new \App\Jobs\SyncUnlockLogs)->everyFifteenMinutes();

// Sync alerts every 10 minutes
Schedule::job(new \App\Jobs\SyncAlerts)->everyTenMinutes();
