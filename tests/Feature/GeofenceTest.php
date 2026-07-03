<?php

use App\Models\CoachSession;
use App\Models\Location;
use App\Models\Schedule;

it('computes haversine distance and radius membership', function () {
    // Senayan-ish reference point.
    $location = new Location([
        'latitude'  => -6.21850000,
        'longitude' => 106.80200000,
        'radius_m'  => 150,
    ]);

    // Same point → 0 m, inside radius.
    expect($location->distanceMetersTo(-6.21850000, 106.80200000))->toBe(0);
    expect($location->isWithinRadius(-6.21850000, 106.80200000))->toBeTrue();

    // ~1 km away → outside 150 m radius.
    $far = $location->distanceMetersTo(-6.22800000, 106.80200000);
    expect($far)->toBeGreaterThan(900);
    expect($location->isWithinRadius(-6.22800000, 106.80200000))->toBeFalse();

    // No coordinates on the venue → cannot verify.
    $blank = new Location(['radius_m' => 200]);
    expect($blank->distanceMetersTo(-6.2, 106.8))->toBeNull();
    expect($blank->isWithinRadius(-6.2, 106.8))->toBeFalse();
});

it('flags a coach session recorded outside the venue radius', function () {
    $location = Location::factory()->create([
        'latitude'  => -6.21850000,
        'longitude' => 106.80200000,
        'radius_m'  => 150,
    ]);
    $schedule = Schedule::factory()->create([
        'location_id' => $location->id,
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => now()->subMinutes(10)->format('H:i:s'),
        'end_time'    => now()->addHour()->format('H:i:s'),
    ]);

    $inside = new CoachSession([
        'latitude'      => -6.21850000,
        'longitude'     => 106.80200000,
        'checked_in_at' => now(),
    ]);
    $inside->setRelation('schedule', $schedule);
    expect($inside->isLocationFlagged())->toBeFalse();
    expect($inside->isFlagged())->toBeFalse();

    $outside = new CoachSession([
        'latitude'      => -6.24000000,
        'longitude'     => 106.80200000,
        'checked_in_at' => now(),
    ]);
    $outside->setRelation('schedule', $schedule);
    expect($outside->isLocationFlagged())->toBeTrue();
    expect($outside->distanceMeters())->toBeGreaterThan($location->radius_m);
});

it('flags a check-in that happens outside the schedule time window', function () {
    $schedule = Schedule::factory()->create([
        'day_of_week' => strtolower(now()->format('l')),
        'start_time'  => '06:00:00',
        'end_time'    => '07:00:00',
    ]);

    $late = new CoachSession(['checked_in_at' => now()->setTime(21, 0)]);
    $late->setRelation('schedule', $schedule);

    expect($late->isTimeFlagged())->toBeTrue();
});
