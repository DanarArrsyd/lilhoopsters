<?php

namespace App\Livewire\Coach;

use App\Models\CoachAttendance;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CheckIn extends Component
{
    public int|string $locationId = '';
    public string $notes          = '';

    public function checkIn(): void
    {
        $this->validate([
            'locationId' => 'required|exists:locations,id',
        ]);

        $coach = Auth::user()->coach;
        if (!$coach) abort(403);

        if ($coach->isCheckedIn()) {
            $this->addError('locationId', 'You are already checked in.');
            return;
        }

        CoachAttendance::create([
            'coach_id'      => $coach->id,
            'location_id'   => $this->locationId,
            'checked_in_at' => now(),
            'notes'         => $this->notes ?: null,
        ]);

        $this->locationId = '';
        $this->notes      = '';
        session()->flash('success', 'Checked in successfully.');
    }

    public function checkOut(): void
    {
        $coach = Auth::user()->coach;
        if (!$coach) abort(403);

        $active = $coach->activeCheckin();
        if (!$active) return;

        $active->update(['checked_out_at' => now()]);
        session()->flash('success', 'Checked out successfully.');
    }

    public function render()
    {
        $coach     = Auth::user()->coach;
        $active    = $coach?->activeCheckin()?->load('location');
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        $history = $coach
            ? CoachAttendance::where('coach_id', $coach->id)
                ->with('location')
                ->latest('checked_in_at')
                ->limit(10)
                ->get()
            : collect();

        return view('livewire.coach.check-in', compact('active', 'locations', 'history'));
    }
}
