<?php

namespace App\Console\Commands;

use App\Services\TransactionExpiryService;
use Illuminate\Console\Command;

class ExpireTransactions extends Command
{
    protected $signature = 'transactions:expire {--days= : Age in days before a pending transaction expires}';

    protected $description = 'Expire stale pending transactions (and their pending enrollments)';

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : TransactionExpiryService::DEFAULT_DAYS;

        $stats = TransactionExpiryService::run($days);

        $this->info("Transactions expired: {$stats['transactions']}");
        $this->info("Enrollments expired:  {$stats['enrollments']}");

        return self::SUCCESS;
    }
}
