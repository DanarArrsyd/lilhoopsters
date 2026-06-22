<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily reminder sweep: expiring packages + unpaid transactions.
// Requires the server cron to run `php artisan schedule:run` every minute.
Schedule::command('reminders:send')->dailyAt('08:00');
