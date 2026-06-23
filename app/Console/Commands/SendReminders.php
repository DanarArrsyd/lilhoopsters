<?php

namespace App\Console\Commands;

use App\Services\ReminderService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'reminders:send {--days=7 : Expiry window in days for renewal reminders}';

    protected $description = 'Send in-app reminders for expiring packages and unpaid transactions';

    public function handle(): int
    {
        $stats = ReminderService::runDueReminders((int) $this->option('days'));

        $this->info("Renewal reminders sent: {$stats['renewal']}");
        $this->info("Payment reminders sent: {$stats['payment']}");

        return self::SUCCESS;
    }
}
