<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\EventRegistration;
use App\Models\Transaction;

/**
 * Expires stale pending transactions.
 *
 * A pending transaction with no proof/verification after EXPIRE_DAYS days
 * is considered abandoned: its status flips to `expired` and `expired_at`
 * is stamped. Any pending enrollment or event registration that was waiting
 * on that payment is expired/cancelled alongside it, so AR, the payment
 * funnel, capacity counts, and event registration lists stop counting dead
 * leads — mirrors what Payments::confirmReject already does for a manually
 * rejected payment.
 */
class TransactionExpiryService
{
    public const DEFAULT_DAYS = 7;

    /** @return array{transactions:int, enrollments:int, event_registrations:int} */
    public static function run(int $days = self::DEFAULT_DAYS): array
    {
        $cutoff = now()->subDays($days);

        $stale = Transaction::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        $txnCount = 0;
        $enrCount = 0;
        $eventRegCount = 0;

        foreach ($stale as $t) {
            $t->update(['status' => 'expired', 'expired_at' => now()]);
            $txnCount++;

            $enrCount += Enrollment::query()
                ->where('transaction_id', $t->id)
                ->where('status', 'pending')
                ->update(['status' => 'expired']);

            $eventRegCount += EventRegistration::query()
                ->where('transaction_id', $t->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        }

        return ['transactions' => $txnCount, 'enrollments' => $enrCount, 'event_registrations' => $eventRegCount];
    }
}
