<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'is_registerable', 'price', 'capacity',
        'start_date', 'end_date',
        'location_id', 'program_id', 'is_active', 'created_by',
    ];

    protected $casts = [
        'start_date'      => 'date',
        'end_date'        => 'date',
        'is_active'       => 'boolean',
        'is_registerable' => 'boolean',
        'price'           => 'integer',
        'capacity'        => 'integer',
    ];

    public function isPaid(): bool
    {
        return (int) $this->price > 0;
    }

    /** Registrations holding a spot (everything except cancelled). */
    public function takenCount(): int
    {
        return $this->registrations()->where('status', '!=', 'cancelled')->count();
    }

    public function isFull(): bool
    {
        return $this->capacity !== null && $this->takenCount() >= $this->capacity;
    }

    public function spotsLeft(): ?int
    {
        return $this->capacity === null ? null : max(0, $this->capacity - $this->takenCount());
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(EventAttendance::class);
    }

    /** Inclusive day count of the event period. */
    public function dayCount(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** True if the event covers today. */
    public function isRunning(): bool
    {
        return $this->is_active
            && today()->betweenIncluded($this->start_date, $this->end_date);
    }

    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function program(): BelongsTo  { return $this->belongsTo(Program::class); }
    public function creator(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    public function enrollments(): BelongsToMany
    {
        return $this->belongsToMany(Enrollment::class, 'event_enrollment')
            ->withPivot('days_added')
            ->withTimestamps();
    }
}
