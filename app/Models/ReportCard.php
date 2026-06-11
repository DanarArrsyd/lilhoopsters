<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportCard extends Model
{
    use HasFactory;
    protected $fillable = [
        'child_id', 'coach_id', 'enrollment_id',
        'period_label', 'period_start', 'period_end',
        'overall_notes', 'status', 'published_at', 'pdf_path',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'published_at' => 'datetime',
    ];

    public function child(): BelongsTo      { return $this->belongsTo(Child::class); }
    public function coach(): BelongsTo      { return $this->belongsTo(Coach::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function scores(): HasMany       { return $this->hasMany(ReportCardScore::class); }
}
