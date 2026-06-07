<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'child_id', 'type', 'schedule_id', 'package_id', 'transaction_id',
        'status', 'member_notes', 'admin_notes',
        'approved_by', 'approved_at', 'started_at', 'expires_at',
        'remaining_sessions', 'total_sessions',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'started_at'  => 'date',
        'expires_at'  => 'date',
    ];

    public function child(): BelongsTo       { return $this->belongsTo(Child::class); }
    public function schedule(): BelongsTo    { return $this->belongsTo(Schedule::class); }
    public function package(): BelongsTo     { return $this->belongsTo(Package::class); }
    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function approvedBy(): BelongsTo  { return $this->belongsTo(User::class, 'approved_by'); }
    public function attendances(): HasMany   { return $this->hasMany(Attendance::class); }
    public function leaveRequests(): HasMany { return $this->hasMany(LeaveRequest::class); }

    public function isActive(): bool
    {
        return $this->status === 'approved'
            && ($this->expires_at === null || $this->expires_at->isFuture())
            && ($this->remaining_sessions === null || $this->remaining_sessions > 0);
    }
}
