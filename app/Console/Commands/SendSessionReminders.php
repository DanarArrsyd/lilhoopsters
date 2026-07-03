<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendSessionReminders extends Command
{
    protected $signature = 'reminders:sessions';

    protected $description = 'Send a combined reminder to each parent whose child has a session tomorrow';

    public function handle(): int
    {
        $sent = ReminderService::remindTomorrowSessions();

        $this->info("Session reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
