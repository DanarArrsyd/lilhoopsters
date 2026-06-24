<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'child_id', 'attendance_date', 'status', 'recorded_by',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function child(): BelongsTo { return $this->belongsTo(Child::class); }
}
