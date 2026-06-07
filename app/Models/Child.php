<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Child extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'birth_date', 'gender',
        'photo', 'school', 'medical_notes', 'qr_identifier',
        'status', 'jersey_name', 'jersey_number', 'registered_at',
    ];

    protected $casts = [
        'birth_date'    => 'date',
        'registered_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (Child $child) {
            if (empty($child->qr_identifier)) {
                $child->qr_identifier = Str::uuid()->toString();
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }

    public function ageInMonths(): int
    {
        return (int) $this->birth_date->diffInMonths(now());
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
