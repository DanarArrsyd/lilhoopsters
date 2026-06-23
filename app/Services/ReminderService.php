<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Transaction;
use Carbon\Carbon;

/**
 * Builds and sends in-app (and WhatsApp, via NotificationService) reminders
 * for two owner-facing concerns:
 *   - Renewal: enrollments about to run out of days/sessions.
 *   - Payment: transactions still pending.
 *
 * Auto runs are de-duplicated so a parent isn't pinged for the same item
 * more than once within COOLDOWN_DAYS. Manual sends (owner clicks a button)
 * pass $force = true and bypass the cooldown.
 */
class ReminderService
{
    public const RENEWAL  = 'renewal_reminder';
    public const PAYMENT  = 'payment_reminder';
    public const COOLDOWN_DAYS = 3;

    // ─── Batch entrypoints (used by the scheduled command) ───────────

    public static function runDueReminders(int $expiringDays = 7): array
    {
        return [
            'renewal' => self::remindExpiring($expiringDays),
            'payment' => self::remindOutstanding(),
        ];
    }

    public static function remindExpiring(int $days = 7): int
    {
        $today      = today();
        $soonCutoff = $today->copy()->addDays($days);

        $enrollments = Enrollment::query()
            ->where('status', 'approved')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today))
            ->where(fn($q) => $q->whereNull('remaining_sessions')->orWhere('remaining_sessions', '>', 0))
            ->where(fn($q) => $q
                ->whereDate('expires_at', '<=', $soonCutoff)
                ->orWhere('remaining_sessions', '<=', 2))
            ->with(['child.parent', 'package'])
            ->get();

        return $enrollments->reduce(fn($n, $e) => $n + (self::sendRenewal($e) ? 1 : 0), 0);
    }

    public static function remindOutstanding(): int
    {
        $pending = Transaction::query()
            ->where('status', 'pending')
            ->with(['user', 'child', 'package'])
            ->get();

        return $pending->reduce(fn($n, $t) => $n + (self::sendPayment($t) ? 1 : 0), 0);
    }

    // ─── Single sends ────────────────────────────────────────────────

    public static function sendRenewal(Enrollment $e, bool $force = false): bool
    {
        $parent = $e->child?->parent;
        if (! $parent) {
            return false;
        }
        if (! $force && self::recentlySent($parent->id, self::RENEWAL, 'enrollment_id', $e->id)) {
            return false;
        }

        $childName = $e->child?->name ?? 'your child';
        $pkg       = $e->package?->name ?? 'training package';

        if ($e->remaining_sessions !== null && $e->remaining_sessions <= 2) {
            $detail = "has {$e->remaining_sessions} sessions left";
        } elseif ($e->expires_at) {
            $detail = 'ends ' . Carbon::parse($e->expires_at)->format('d M Y');
        } else {
            $detail = 'is ending soon';
        }

        NotificationService::send(
            $parent->id,
            self::RENEWAL,
            'Package Expiring Soon',
            "The {$pkg} package for {$childName} {$detail}. Renew now so training isn't interrupted.",
            ['enrollment_id' => $e->id, 'child_id' => $e->child_id],
        );

        return true;
    }

    public static function sendPayment(Transaction $t, bool $force = false): bool
    {
        $parent = $t->user;
        if (! $parent) {
            return false;
        }
        if (! $force && self::recentlySent($parent->id, self::PAYMENT, 'transaction_id', $t->id)) {
            return false;
        }

        $amount = 'Rp ' . number_format($t->amount, 0, ',', '.');
        $pkg    = $t->package?->name ?? 'package';

        NotificationService::send(
            $parent->id,
            self::PAYMENT,
            'Outstanding Payment',
            "Payment for {$pkg} of {$amount} is still pending. Code: {$t->transaction_code}.",
            ['transaction_id' => $t->id],
        );

        return true;
    }

    // ─── Dedup ───────────────────────────────────────────────────────

    private static function recentlySent(int $userId, string $type, string $key, int $id): bool
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('type', $type)
            ->where('created_at', '>=', now()->subDays(self::COOLDOWN_DAYS))
            ->get(['data'])
            ->contains(fn($n) => ($n->data[$key] ?? null) === $id);
    }
}
