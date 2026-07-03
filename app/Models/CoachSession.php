<?php

namespace App\Models;

use App\Models\Concerns\HasAttendanceGeofence;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachSession extends Model
{
    use HasAttendanceGeofence;

    protected $fillable = [
        'schedule_id', 'coach_id', 'session_date', 'role',
        'ip_address', 'latitude', 'longitude', 'checked_in_at', 'checked_out_at', 'auto_closed',
    ];

    protected $casts = [
        'session_date'   => 'date',
        'checked_in_at'  => 'datetime',
        'checked_out_at' => 'datetime',
        'latitude'       => 'float',
        'longitude'      => 'float',
        'auto_closed'    => 'boolean',
    ];

    /**
     * Auto check-out any still-open sessions whose schedule has already ended,
     * closing them at the scheduled end time. Returns the number closed.
     */
    public static function autoCloseStale(): int
    {
        $closed = 0;

        static::whereNull('checked_out_at')->with('schedule')->get()->each(function (self $session) use (&$closed) {
            if (! $session->schedule) {
                return;
            }

            $end = Carbon::parse($session->session_date->toDateString() . ' ' . $session->schedule->end_time);
            if ($session->checked_in_at && $end->lt($session->checked_in_at)) {
                $end->addDay(); // overnight session
            }

            if (now()->greaterThan($end)) {
                $session->update(['checked_out_at' => $end, 'auto_closed' => true]);
                $closed++;
            }
        });

        return $closed;
    }

    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function coach(): BelongsTo    { return $this->belongsTo(Coach::class); }

    public function isActive(): bool
    {
        return $this->checked_out_at === null;
    }

    public function actionTimestamp(): ?Carbon
    {
        return $this->checked_in_at;
    }
}
