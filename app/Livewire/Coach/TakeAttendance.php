<?php

namespace App\Livewire\Coach;

use App\Models\Attendance;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TakeAttendance extends Component
{
    public ?int $scheduleId = null;
    public string $date     = '';
    public array $roster    = [];
    public bool $saved      = false;

    public function mount(): void
    {
        $this->date = now()->toDateString();

        $coach = Auth::user()->coach;
        if ($coach) {
            $today = strtolower(now()->format('l'));
            $first = $coach->schedules()
                ->where('is_active', true)
                ->where('day_of_week', $today)
                ->first();
            if ($first) {
                $this->scheduleId = $first->id;
                $this->loadRoster();
            }
        }
    }

    public function updatedScheduleId(): void
    {
        $this->roster = [];
        $this->saved  = false;
        $this->loadRoster();
    }

    public function updatedDate(): void
    {
        $this->saved = false;
        $this->loadRoster();
    }

    public function loadRoster(): void
    {
        if (!$this->scheduleId) {
            $this->roster = [];
            return;
        }

        $this->authorizeCoachOwnsSchedule();

        $enrollments = Enrollment::where('schedule_id', $this->scheduleId)
            ->where('status', 'approved')
            ->with('child')
            ->get();

        $existing = Attendance::where('schedule_id', $this->scheduleId)
            ->whereDate('attended_at', $this->date)
            ->pluck('status', 'child_id');

        $this->roster = $enrollments->map(fn($e) => [
            'child_id'      => $e->child_id,
            'enrollment_id' => $e->id,
            'name'          => $e->child->name,
            'status'        => $existing[$e->child_id] ?? 'present',
        ])->toArray();
    }

    public function setStatus(int $childId, string $status): void
    {
        foreach ($this->roster as &$row) {
            if ($row['child_id'] === $childId) {
                $row['status'] = $status;
                break;
            }
        }
    }

    public function saveAttendance(): void
    {
        if (!$this->scheduleId || empty($this->roster)) {
            return;
        }

        $this->authorizeCoachOwnsSchedule();

        $coach = Auth::user()->coach;

        foreach ($this->roster as $row) {
            Attendance::updateOrCreate(
                [
                    'child_id'    => $row['child_id'],
                    'schedule_id' => $this->scheduleId,
                    'attended_at' => $this->date,
                ],
                [
                    'enrollment_id' => $row['enrollment_id'],
                    'coach_id'      => $coach->id,
                    'status'        => $row['status'],
                    'source'        => 'manual',
                ]
            );
        }

        $this->saved = true;
        session()->flash('attendance_success', 'Attendance saved.');
    }

    private function authorizeCoachOwnsSchedule(): void
    {
        $coach = Auth::user()->coach;
        if (!$coach) {
            abort(403);
        }
        $owns = $coach->schedules()->where('id', $this->scheduleId)->exists();
        if (!$owns) {
            abort(403);
        }
    }

    public function render()
    {
        $coach = Auth::user()->coach;

        $schedules = $coach
            ? $coach->schedules()
                ->where('is_active', true)
                ->with(['location', 'program'])
                ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('livewire.coach.take-attendance', compact('schedules'));
    }
}
