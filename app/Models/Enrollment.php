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

    /** SQL-level mirror of isActive() — approved, not expired, quota not exhausted. */
    public function scopeActive($query)
    {
        return $query->where('status', 'approved')
            ->where(fn($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', today()))
            ->where(fn($q) => $q->whereNull('remaining_sessions')->orWhere('remaining_sessions', '>', 0));
    }

    /** Burn one session from the package quota (session-based packages only). */
    public function consumeSession(): void
    {
        if ($this->remaining_sessions !== null && $this->remaining_sessions > 0) {
            $this->decrement('remaining_sessions');
        }
    }

    /** Give a session back — correcting a record that previously consumed one. */
    public function restoreSession(): void
    {
        if ($this->remaining_sessions === null) {
            return;
        }

        if ($this->total_sessions === null || $this->remaining_sessions < $this->total_sessions) {
            $this->increment('remaining_sessions');
        }
    }
}
