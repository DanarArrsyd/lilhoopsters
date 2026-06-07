<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'city', 'maps_url', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function coaches(): BelongsToMany
    {
        return $this->belongsToMany(Coach::class, 'coach_locations');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }
}
