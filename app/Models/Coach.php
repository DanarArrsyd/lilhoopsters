<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coach extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'phone', 'specialization', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'coach_locations');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(CoachAttendance::class);
    }

    public function coachSessions(): HasMany
    {
        return $this->hasMany(CoachSession::class);
    }

    public function activeCheckin(): ?CoachAttendance
    {
        return $this->attendances()
            ->where('expires_at', '>', now())
            ->whereNull('checked_out_at')
            ->latest()
            ->first();
    }

    public function isCheckedIn(): bool
    {
        return $this->activeCheckin() !== null;
    }
}
