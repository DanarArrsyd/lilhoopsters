<?php

namespace App\Models;

use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Email the parent when their child is checked in (present).
        static::created(function (Attendance $attendance) {
            if ($attendance->status !== 'present') {
                return;
            }

            $attendance->loadMissing(['child.parent', 'schedule.program', 'schedule.location']);
            $parent = $attendance->child?->parent;
            if (! $parent) {
                return;
            }

            $childName = $attendance->child->name;
            $program   = $attendance->schedule?->program?->name ?? 'training';
            $location  = $attendance->schedule?->location?->name;
            $date      = Carbon::parse($attendance->attended_at)->format('d M Y');

            $where = $location ? " at {$location}" : '';

            NotificationService::send(
                $parent->id,
                'attendance_checkin',
                'Check-in confirmed',
                "{$childName} has checked in to {$program}{$where} on {$date}.",
                ['child_id' => $attendance->child_id],
                email: true,
            );
        });
    }

    protected $fillable = [
        'child_id', 'enrollment_id', 'schedule_id', 'coach_id',
        'leave_request_id', 'make_up_class_id',
        'status', 'source', 'attended_at', 'notes',
        'latitude', 'longitude', 'ip_address', 'session_deducted',
    ];

    protected $casts = [
        'attended_at'      => 'datetime',
        'session_deducted' => 'boolean',
        'latitude'         => 'decimal:8',
        'longitude'        => 'decimal:8',
    ];

    public function child(): BelongsTo        { return $this->belongsTo(Child::class); }
    public function enrollment(): BelongsTo   { return $this->belongsTo(Enrollment::class); }
    public function schedule(): BelongsTo     { return $this->belongsTo(Schedule::class); }
    public function coach(): BelongsTo        { return $this->belongsTo(Coach::class); }
    public function leaveRequest(): BelongsTo { return $this->belongsTo(LeaveRequest::class); }
    public function makeUpClass(): BelongsTo  { return $this->belongsTo(MakeUpClass::class); }
}
