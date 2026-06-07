<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MakeUpClass extends Model
{
    protected $fillable = [
        'child_id', 'enrollment_id', 'leave_request_id', 'target_schedule_id',
        'target_date', 'status', 'admin_notes', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'target_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function child(): BelongsTo          { return $this->belongsTo(Child::class); }
    public function enrollment(): BelongsTo     { return $this->belongsTo(Enrollment::class); }
    public function leaveRequest(): BelongsTo   { return $this->belongsTo(LeaveRequest::class); }
    public function targetSchedule(): BelongsTo { return $this->belongsTo(Schedule::class, 'target_schedule_id'); }
    public function approvedBy(): BelongsTo     { return $this->belongsTo(User::class, 'approved_by'); }
}
