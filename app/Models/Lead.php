<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_name', 'child_name', 'whatsapp', 'source',
        'location_id', 'program_id', 'status', 'trial_date',
        'notes', 'created_by', 'converted_child_id',
    ];

    protected $casts = [
        'trial_date' => 'date',
    ];

    public const STATUSES = ['new', 'contacted', 'trial_scheduled', 'trial_done', 'converted', 'lost'];
    public const SOURCES  = ['walk_in', 'instagram', 'whatsapp', 'referral', 'web', 'other'];

    /** Statuses that count as an open (still-in-pipeline) lead. */
    public const OPEN_STATUSES = ['new', 'contacted', 'trial_scheduled', 'trial_done'];

    public function location(): BelongsTo       { return $this->belongsTo(Location::class); }
    public function program(): BelongsTo        { return $this->belongsTo(Program::class); }
    public function creator(): BelongsTo         { return $this->belongsTo(User::class, 'created_by'); }
    public function convertedChild(): BelongsTo  { return $this->belongsTo(Child::class, 'converted_child_id'); }
}
