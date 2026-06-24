<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'start_date', 'end_date',
        'location_id', 'program_id', 'is_active', 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

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
