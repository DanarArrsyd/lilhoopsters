<?php

namespace App\Models\Concerns;

use Carbon\Carbon;

/**
 * Shared geofence / time-window checks for attendance-style records that carry
 * a schedule, GPS coordinates, and an action timestamp.
 *
 * The consuming model must expose:
 *   - a `schedule` relation (with a `location`)
 *   - `latitude` / `longitude` attributes
 *   - implement actionTimestamp(): the real moment the scan/check-in happened.
 */
trait HasAttendanceGeofence
{
    abstract public function actionTimestamp(): ?Carbon;

    /** Distance in metres from the recorded point to the venue, or null if unknown. */
    public function distanceMeters(): ?int
    {
        return $this->schedule?->location?->distanceMetersTo($this->latitude, $this->longitude);
    }

    /** True when GPS is known and falls outside the venue radius. */
    public function isLocationFlagged(): bool
    {
        $location = $this->schedule?->location;

        if (! $location || ! $location->hasCoordinates() || $this->latitude === null || $this->longitude === null) {
            return false; // cannot verify → not flagged
        }

        return ! $location->isWithinRadius($this->latitude, $this->longitude);
    }

    /** True when the action happened outside the schedule's time window. */
    public function isTimeFlagged(): bool
    {
        $moment = $this->actionTimestamp();

        if (! $this->schedule || ! $moment) {
            return false;
        }

        return ! $this->schedule->isWithinTimeWindow($moment);
    }

    /** Any geofence/time anomaly worth surfacing to an admin. */
    public function isFlagged(): bool
    {
        return $this->isLocationFlagged() || $this->isTimeFlagged();
    }
}
