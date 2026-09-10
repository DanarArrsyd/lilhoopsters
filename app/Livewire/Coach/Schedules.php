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

        // Regular schedules aren't assigned a coach_id (any checked-in coach
        // can run them) — only private schedules are coach-specific. A bare
        // coach_id match here would show nothing for a coach who only runs
        // regular/group classes.
        $schedules = $coach
            ? Schedule::where('is_active', true)
                ->where(function ($q) use ($coach) {
                    $q->where('type', 'regular')
                      ->orWhere(fn($q2) => $q2->where('type', 'private')->where('coach_id', $coach->id));
                })
                ->with(['location', 'program'])
                ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
                ->orderBy('start_time')
                ->get()
            : collect();

        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

        return view('livewire.coach.schedules', compact('schedules', 'days'));
    }
}
