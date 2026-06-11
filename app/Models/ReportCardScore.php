<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportCardScore extends Model
{
    use HasFactory;
    protected $fillable = ['report_card_id', 'category', 'score', 'notes'];

    protected $casts = ['score' => 'integer'];

    public function reportCard(): BelongsTo { return $this->belongsTo(ReportCard::class); }
}
