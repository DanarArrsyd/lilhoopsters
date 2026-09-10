<?php

namespace App\Models;

use App\Models\Concerns\HasAttendanceGeofence;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory, HasAttendanceGeofence;

    protected static function booted(): void
    {
        // A package's quota is only burned by a session the child actually used
        // (present) or missed without an excuse (no_show). An approved leave
        // (sick/permit) is compensated separately via a make-up class, so it
        // must not cost a session — the status decides which side of that
        // line an attendance record falls on, regardless of who created it.
        static::creating(function (Attendance $attendance) {
            $attendance->session_deducted = self::statusDeductsSession($attendance->status);
        });

        static::created(function (Attendance $attendance) {
            if ($attendance->session_deducted) {
                $attendance->enrollment?->consumeSession();
            }

            // Attending the make-up session is what the booking was for —
            // close it out so it stops showing as an open "approved" request.
            if ($attendance->make_up_class_id) {
                $attendance->makeUpClass?->update(['status' => 'completed']);
            }
        });

        static::updating(function (Attendance $attendance) {
            if ($attendance->isDirty('status')) {
                $attendance->session_deducted = self::statusDeductsSession($attendance->status);
            }
        });

        static::updated(function (Attendance $attendance) {
            if (! $attendance->wasChanged('session_deducted')) {
                return;
            }

            $enrollment = $attendance->enrollment;
            if (! $enrollment) {
                return;
            }

            $attendance->session_deducted
                ? $enrollment->consumeSession()
                : $enrollment->restoreSession();
        });

        static::deleted(function (Attendance $attendance) {
            if ($attendance->session_deducted) {
                $attendance->enrollment?->restoreSession();
            }

            // Undoing the record reopens the make-up booking — it's not
            // completed until the child actually shows up.
            if ($attendance->make_up_class_id) {
                $attendance->makeUpClass()->where('status', 'completed')->update(['status' => 'approved']);
            }
        });

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

    private static function statusDeductsSession(?string $status): bool
    {
        // make_up is the delivery of a session an earlier excused absence
        // deferred — it costs a session same as present/no_show would have.
        return in_array($status, ['present', 'no_show', 'make_up'], true);
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

    /** The real moment the scan happened (attended_at may be date-only). */
    public function actionTimestamp(): ?Carbon
    {
        return $this->created_at;
    }
}
