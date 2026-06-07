<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'min_age_months', 'max_age_months', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function matchesChild(Child $child): bool
    {
        $age = $child->ageInMonths();
        return $age >= $this->min_age_months && $age <= $this->max_age_months;
    }
}
