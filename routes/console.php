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
