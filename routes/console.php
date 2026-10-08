<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sla:check-escalation')->hourly();
Schedule::command('reports:auto-close-stale')->dailyAt('02:00');
Schedule::command('reports:daily-digest')->dailyAt('07:00');
Schedule::command('queue:monitor default:100')->everyMinute();
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('activitylog:clean --days=365')->dailyAt('03:00');

// Heartbeat scheduler: dipakai HealthMonitor untuk mendeteksi cron yang mati.
Schedule::call(function () {
    $file = config('monitoring.heartbeat_file');
    if (is_string($file) && $file !== '') {
        @file_put_contents($file, json_encode(['ran_at' => now()->toIso8601String()]));
    }
})->everyMinute()->name('schedule-heartbeat');

// Monitoring: health system dan error log (alert jika gagal/terdeteksi).
Schedule::command('monitor:health --alert')->hourlyAt(5);
Schedule::command('monitor:errors --alert --hours=1')->hourlyAt(15);
