<?php

namespace App\Livewire\Coach;

use App\Models\Attendance;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Roster extends Component
{
    public int|string $scheduleId = '';
    public string $date           = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();

        $coach = Auth::user()->coach;
        if ($coach) {
            $today = strtolower(now()->format('l'));
            $first = $coach->schedules()->where('is_active', true)->where('day_of_week', $today)->first();
            if ($first) {
                $this->scheduleId = $first->id;
            }
        }
    }

    public function render()
    {
        $coach     = Auth::user()->coach;
        $schedules = $coach
            ? $coach->schedules()->where('is_active', true)->with(['program', 'location'])->get()
            : collect();

        $roster = collect();
        $stats  = ['total' => 0, 'present' => 0, 'absent' => 0, 'sick' => 0, 'permit' => 0, 'not_recorded' => 0];

        if ($this->scheduleId && $coach) {
            $this->authorizeOwns();

            $enrollments = Enrollment::where('schedule_id', $this->scheduleId)
                ->where('status', 'approved')
                ->with('child')
                ->get();

            $existing = Attendance::where('schedule_id', $this->scheduleId)
                ->whereDate('attended_at', $this->date)
                ->pluck('status', 'child_id');

            $stats['total'] = $enrollments->count();

            $roster = $enrollments->map(function ($e) use ($existing, &$stats) {
                $status = $existing[$e->child_id] ?? null;

                if ($status === 'present')      $stats['present']++;
                elseif ($status === 'no_show')  $stats['absent']++;
                elseif ($status === 'sick')     $stats['sick']++;
                elseif ($status === 'permit')   $stats['permit']++;
                else                            $stats['not_recorded']++;

                return [
                    'child_id' => $e->child_id,
                    'name'     => $e->child->name,
                    'jersey'   => $e->child->jersey_number,
                    'status'   => $status,
                ];
            });
        }

        return view('livewire.coach.roster', compact('schedules', 'roster', 'stats'));
    }

    private function authorizeOwns(): void
    {
        $coach = Auth::user()->coach;
        if (!$coach || !$coach->schedules()->where('id', $this->scheduleId)->exists()) {
            abort(403);
        }
    }
}
