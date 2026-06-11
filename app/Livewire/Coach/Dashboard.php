<?php

namespace App\Livewire\Coach;

use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $coach = Auth::user()->coach;

        if (!$coach) {
            $this->stats = [
                'total_schedules'  => 0,
                'today_schedules'  => 0,
                'active_students'  => 0,
            ];
            return;
        }

        $today = strtolower(now()->format('l'));

        $schedules = $coach->schedules()->where('is_active', true)->with('enrollments')->get();

        $this->stats = [
            'total_schedules' => $schedules->count(),
            'today_schedules' => $schedules->where('day_of_week', $today)->count(),
            'active_students' => $schedules->sum(
                fn($s) => $s->enrollments->where('status', 'approved')->count()
            ),
        ];
    }

    public function render()
    {
        $coach = Auth::user()->coach;
        $todaySchedules = collect();

        if ($coach) {
            $today = strtolower(now()->format('l'));
            $todaySchedules = $coach->schedules()
                ->where('is_active', true)
                ->where('day_of_week', $today)
                ->with(['location', 'program'])
                ->orderBy('start_time')
                ->get();
        }

        return view('livewire.coach.dashboard', compact('todaySchedules'));
    }
}
