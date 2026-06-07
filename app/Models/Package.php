<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id', 'name', 'type', 'price',
        'session_count', 'validity_days',
        'period_start', 'period_end',
        'description', 'is_active', 'is_popular',
    ];

    protected $casts = [
        'price'        => 'integer',
        'is_active'    => 'boolean',
        'is_popular'   => 'boolean',
        'period_start' => 'date',
        'period_end'   => 'date',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function formattedPrice(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }
}
