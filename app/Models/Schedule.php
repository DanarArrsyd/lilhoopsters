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
}
