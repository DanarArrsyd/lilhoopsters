<?php

namespace App\Services;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Handles the package "freeze" around an event: while regular classes are
 * paused, affected active enrollments get their expiry pushed back by the
 * event length so members don't lose validity. Every shift is recorded on
 * the event_enrollment pivot so it can be reversed cleanly.
 */
class EventService
{
    /** Approved program enrollments still valid at the event start, within scope. */
    public static function affectedEnrollments(Event $event): Collection
    {
        return Enrollment::query()
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', $event->start_date)
            ->whereHas('schedule', function ($q) use ($event) {
                if ($event->location_id) $q->where('location_id', $event->location_id);
                if ($event->program_id)  $q->where('program_id', $event->program_id);
            })
            ->get();
    }

    /** Push expiry forward for affected enrollments. Idempotent. Returns count. */
    public static function applyFreeze(Event $event): int
    {
        if (! $event->is_active) {
            return 0;
        }

        $days    = $event->dayCount();
        $already = $event->enrollments()->pluck('enrollments.id')->all();
        $count   = 0;

        foreach (self::affectedEnrollments($event) as $enrollment) {
            if (in_array($enrollment->id, $already, true)) {
                continue; // already frozen for this event
            }

            $enrollment->update([
                'expires_at' => Carbon::parse($enrollment->expires_at)->addDays($days),
            ]);
            $event->enrollments()->attach($enrollment->id, ['days_added' => $days]);
            $count++;
        }

        return $count;
    }

    /** Undo the freeze (e.g. event deleted/deactivated/edited). Returns count. */
    public static function reverseFreeze(Event $event): int
    {
        $count = 0;

        foreach ($event->enrollments()->get() as $enrollment) {
            $days = (int) $enrollment->pivot->days_added;
            if ($enrollment->expires_at) {
                $enrollment->update([
                    'expires_at' => Carbon::parse($enrollment->expires_at)->subDays($days),
                ]);
            }
            $count++;
        }

        $event->enrollments()->detach();

        return $count;
    }

    /**
     * Register a child for an event. Free events confirm immediately; paid
     * events create a pending transaction and stay pending until verified.
     *
     * @throws RuntimeException with a user-facing message on any problem.
     */
    public static function register(Event $event, Child $child): EventRegistration
    {
        if (! $event->is_registerable) {
            throw new RuntimeException('This event is not open for registration.');
        }
        if ($event->registrations()->where('child_id', $child->id)->where('status', '!=', 'cancelled')->exists()) {
            throw new RuntimeException('This child is already registered for the event.');
        }
        if ($event->isFull()) {
            throw new RuntimeException('This event is already full.');
        }

        return DB::transaction(function () use ($event, $child) {
            $registration = EventRegistration::create([
                'event_id'      => $event->id,
                'child_id'      => $child->id,
                'status'        => $event->isPaid() ? 'pending' : 'confirmed',
                'registered_at' => now(),
            ]);

            if ($event->isPaid()) {
                $transaction = Transaction::create([
                    'user_id'    => $child->user_id,
                    'child_id'   => $child->id,
                    'package_id' => null,
                    'amount'     => $event->price,
                    'status'     => 'pending',
                ]);
                $registration->update(['transaction_id' => $transaction->id]);
            }

            return $registration;
        });
    }

    /** Active events that pause a given schedule's location/program. */
    public static function eventsForSchedule(?int $locationId, ?int $programId): Collection
    {
        return Event::query()
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('location_id')->orWhere('location_id', $locationId))
            ->where(fn($q) => $q->whereNull('program_id')->orWhere('program_id', $programId))
            ->get();
    }
}
