<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LeaveRequest extends Model
{
    protected $fillable = [
        'child_id', 'enrollment_id', 'schedule_id',
        'leave_date', 'type', 'reason', 'attachment',
        'status', 'admin_notes', 'reviewed_by', 'reviewed_at', 'auto_approve_at',
    ];

    protected $casts = [
        'leave_date'      => 'date',
        'reviewed_at'     => 'datetime',
        'auto_approve_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (LeaveRequest $lr) {
            if (empty($lr->auto_approve_at)) {
                $lr->auto_approve_at = now()->addHours(72);
            }
        });
    }

    public function child(): BelongsTo      { return $this->belongsTo(Child::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function schedule(): BelongsTo   { return $this->belongsTo(Schedule::class); }
    public function reviewedBy(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function makeUpClass(): HasOne   { return $this->hasOne(MakeUpClass::class); }

    public function isApproved(): bool
    {
        return in_array($this->status, ['approved', 'auto_approved']);
    }
}
