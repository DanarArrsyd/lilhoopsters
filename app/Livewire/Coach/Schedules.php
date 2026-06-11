<?php

namespace App\Livewire\Coach;

use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Schedules extends Component
{
    public function render()
    {
        $coach = Auth::user()->coach;

        $schedules = $coach
            ? $coach->schedules()
                ->with(['location', 'program'])
                ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
                ->orderBy('start_time')
                ->get()
            : collect();

        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

        return view('livewire.coach.schedules', compact('schedules', 'days'));
    }
}
