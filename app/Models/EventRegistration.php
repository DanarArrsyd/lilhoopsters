<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'child_id', 'status', 'transaction_id', 'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function event(): BelongsTo       { return $this->belongsTo(Event::class); }
    public function child(): BelongsTo        { return $this->belongsTo(Child::class); }
    public function transaction(): BelongsTo  { return $this->belongsTo(Transaction::class); }
}
