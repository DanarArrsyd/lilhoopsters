<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'address', 'city', 'maps_url', 'is_active', 'latitude', 'longitude', 'radius_m'];
    protected $casts = [
        'is_active' => 'boolean',
        'latitude'  => 'float',
        'longitude' => 'float',
        'radius_m'  => 'integer',
    ];

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Great-circle distance in metres from this venue to a point (Haversine).
     * Returns null when either the venue or the point lacks coordinates.
     */
    public function distanceMetersTo(?float $lat, ?float $lng): ?int
    {
        if (! $this->hasCoordinates() || $lat === null || $lng === null) {
            return null;
        }

        $earth = 6371000; // metres
        $dLat  = deg2rad($lat - $this->latitude);
        $dLng  = deg2rad($lng - $this->longitude);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) * sin($dLng / 2) ** 2;

        return (int) round($earth * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    /** True when the point falls inside the venue's allowed radius. */
    public function isWithinRadius(?float $lat, ?float $lng): bool
    {
        $distance = $this->distanceMetersTo($lat, $lng);

        return $distance !== null && $distance <= ($this->radius_m ?: 200);
    }

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
