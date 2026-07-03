<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id', 'program_id', 'coach_id',
        'day_of_week', 'start_time', 'end_time',
        'max_capacity', 'is_active', 'type',
    ];

    protected $casts = ['is_active' => 'boolean'];

    public function location(): BelongsTo  { return $this->belongsTo(Location::class); }
    public function program(): BelongsTo   { return $this->belongsTo(Program::class); }
    public function coach(): BelongsTo     { return $this->belongsTo(Coach::class); }
    public function enrollments(): HasMany    { return $this->hasMany(Enrollment::class); }
    public function attendances(): HasMany    { return $this->hasMany(Attendance::class); }
    public function coachSessions(): HasMany  { return $this->hasMany(CoachSession::class); }

    public function approvedEnrollmentsCount(): int
    {
        return $this->enrollments()
            ->where('status', 'approved')
            ->where('type', 'program')
            ->count();
    }

    public function hasCapacity(): bool
    {
        return $this->approvedEnrollmentsCount() < $this->max_capacity;
    }

    /** How early (minutes before start) a coach may check in. */
    const CHECKIN_EARLY_MINUTES = 30;

    /**
     * Check-in window for a given date: [opensAt, endsAt, startsAt].
     * opensAt = start − early grace; endsAt = schedule end (hard cap).
     */
    public function checkInWindow(?\Carbon\Carbon $onDate = null): array
    {
        $date  = ($onDate ?? now())->toDateString();
        $start = \Carbon\Carbon::parse($date . ' ' . $this->start_time);
        $end   = \Carbon\Carbon::parse($date . ' ' . $this->end_time);
        if ($end->lte($start)) {
            $end->addDay(); // overnight session
        }

        return [$start->copy()->subMinutes(self::CHECKIN_EARLY_MINUTES), $end, $start];
    }

    /** 'early' (too soon) | 'open' (allowed) | 'ended' (session over). */
    public function checkInState(?\Carbon\Carbon $now = null): string
    {
        $now = $now ?? now();
        [$opens, $ends] = $this->checkInWindow($now);

        if ($now->lt($opens)) return 'early';
        if ($now->gt($ends))  return 'ended';

        return 'open';
    }

    public function isCheckInOpen(?\Carbon\Carbon $now = null): bool
    {
        return $this->checkInState($now) === 'open';
    }

    /**
     * Whether a moment falls within this schedule's time-of-day window,
     * allowing a grace period before start and after end.
     */
    public function isWithinTimeWindow(\Carbon\Carbon $moment, int $graceMinutes = 30): bool
    {
        $date  = $moment->copy()->toDateString();
        $start = \Carbon\Carbon::parse($date . ' ' . $this->start_time);
        $end   = \Carbon\Carbon::parse($date . ' ' . $this->end_time);
        if ($end->lte($start)) {
            $end->addDay(); // overnight window
        }

        return $moment->betweenIncluded(
            $start->copy()->subMinutes($graceMinutes),
            $end->copy()->addMinutes($graceMinutes),
        );
    }
}
