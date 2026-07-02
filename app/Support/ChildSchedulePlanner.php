<?php

namespace App\Support;

use App\Models\Child;
use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ChildSchedulePlanner
{
    public static function approvedEnrollments(Child $child): Collection
    {
        return Enrollment::where('child_id', $child->id)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id')
            ->with(['schedule.program', 'schedule.location', 'schedule.coach.user'])
            ->get();
    }

    public static function isSessionValidOn(Enrollment $enrollment, Carbon $date): bool
    {
        $day = $date->copy()->startOfDay();
        $weekday = strtolower($day->format('l'));

        if ($enrollment->schedule->day_of_week !== $weekday) {
            return false;
        }

        if ($enrollment->started_at && $day->lt($enrollment->started_at->copy()->startOfDay())) {
            return false;
        }

        if ($enrollment->total_sessions && $enrollment->started_at) {
            $firstSession = $enrollment->started_at->copy()->startOfDay();
            while (strtolower($firstSession->format('l')) !== $weekday) {
                $firstSession->addDay();
            }
            if ($day->lt($firstSession)) {
                return false;
            }
            $sessionNumber = intdiv($firstSession->diffInDays($day), 7) + 1;
            if ($sessionNumber > $enrollment->total_sessions) {
                return false;
            }
        } elseif ($enrollment->expires_at && $day->gt($enrollment->expires_at->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    public static function sessionsOn(Collection $enrollments, Carbon $date): Collection
    {
        return $enrollments->filter(
            fn (Enrollment $e) => self::isSessionValidOn($e, $date)
        )->values();
    }

    public static function nextSession(Child $child): ?array
    {
        $enrollments = self::approvedEnrollments($child);

        if ($enrollments->isEmpty()) {
            return null;
        }

        for ($i = 0; $i < 14; $i++) {
            $date = now()->copy()->addDays($i);
            $sessions = self::sessionsOn($enrollments, $date);

            if ($sessions->isNotEmpty()) {
                $enrollment = $sessions->first();

                return [
                    'program'  => $enrollment->schedule->program->name,
                    'location' => $enrollment->schedule->location->name,
                    'coach'    => $enrollment->schedule->coach?->user?->name,
                    'date'     => $date,
                    'start'    => $enrollment->schedule->start_time,
                    'end'      => $enrollment->schedule->end_time,
                ];
            }
        }

        return null;
    }

    public static function weekSessions(Child $child): Collection
    {
        $enrollments = self::approvedEnrollments($child);
        $weekStart   = now()->startOfWeek(Carbon::MONDAY);
        $days        = collect(range(0, 6))->map(fn (int $i) => $weekStart->copy()->addDays($i));

        return $days
            ->mapWithKeys(function (Carbon $date) use ($enrollments) {
                $sessions = self::sessionsOn($enrollments, $date)->map(fn (Enrollment $e) => [
                    'program'  => $e->schedule->program->name,
                    'location' => $e->schedule->location->name,
                    'start'    => $e->schedule->start_time,
                    'end'      => $e->schedule->end_time,
                ]);

                return [strtolower($date->format('l')) => $sessions];
            })
            ->filter(fn (Collection $sessions) => $sessions->isNotEmpty());
    }
}
