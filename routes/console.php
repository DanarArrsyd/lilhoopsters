<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Expire stale pending transactions first, so the reminder sweep and the
// owner AR/funnel numbers don't count dead payments.
Schedule::command('transactions:expire')->dailyAt('02:00');

// Daily reminder sweep: expiring packages + unpaid transactions.
// Requires the server cron to run `php artisan schedule:run` every minute.
Schedule::command('reminders:send')->dailyAt('08:00');
