<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachAttendance extends Model
{
    protected $fillable = [
        'coach_id', 'location_id', 'checked_in_at',
        'checked_out_at', 'expires_at', 'notes',
    ];

    protected $casts = [
        'checked_in_at'  => 'datetime',
        'checked_out_at' => 'datetime',
        'expires_at'     => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (CoachAttendance $ca) {
            $ca->expires_at = now()->addHours(8);
        });
    }

    public function coach(): BelongsTo    { return $this->belongsTo(Coach::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }

    public function isActive(): bool
    {
        return $this->expires_at->isFuture() && $this->checked_out_at === null;
    }
}
