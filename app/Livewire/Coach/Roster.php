<?php

namespace App\Livewire\Coach;

use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Schedule;
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
            $first = Schedule::where('is_active', true)
                ->where('day_of_week', $today)
                ->where(function ($q) use ($coach) {
                    $q->where('type', 'regular')
                      ->orWhere(fn($q2) => $q2->where('type', 'private')->where('coach_id', $coach->id));
                })
                ->first();
            if ($first) {
                $this->scheduleId = $first->id;
            }
        }
    }

    public function render()
    {
        $coach = Auth::user()->coach;

        // Show all regular schedules + this coach's private schedules
        $schedules = $coach
            ? Schedule::where('is_active', true)
                ->where(function ($q) use ($coach) {
                    $q->where('type', 'regular')
                      ->orWhere(fn($q2) => $q2->where('type', 'private')->where('coach_id', $coach->id));
                })
                ->with(['program', 'location'])
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get()
            : collect();

        $roster = collect();
        $stats  = ['total' => 0, 'present' => 0, 'absent' => 0, 'sick' => 0, 'permit' => 0, 'not_recorded' => 0];

        if ($this->scheduleId && $coach) {
            // A private schedule's roster is visible only to its assigned coach.
            $schedule = Schedule::find($this->scheduleId);
            if ($schedule && $schedule->type === 'private' && $schedule->coach_id !== $coach->id) {
                abort(403);
            }

            $enrollments = Enrollment::where('schedule_id', $this->scheduleId)
                ->where('status', 'approved')
                ->where('type', 'program')
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
            })->sortBy('name')->values();
        }

        return view('livewire.coach.roster', compact('schedules', 'roster', 'stats'));
    }
}
