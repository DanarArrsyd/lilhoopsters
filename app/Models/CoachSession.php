<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachSession extends Model
{
    protected $fillable = [
        'schedule_id', 'coach_id', 'session_date', 'role',
        'ip_address', 'latitude', 'longitude', 'checked_in_at', 'checked_out_at',
    ];

    protected $casts = [
        'session_date'   => 'date',
        'checked_in_at'  => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function schedule(): BelongsTo { return $this->belongsTo(Schedule::class); }
    public function coach(): BelongsTo    { return $this->belongsTo(Coach::class); }

    public function isActive(): bool
    {
        return $this->checked_out_at === null;
    }
}
