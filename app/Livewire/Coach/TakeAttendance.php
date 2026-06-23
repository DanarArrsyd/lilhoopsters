<?php

namespace App\Livewire\Coach;

use App\Models\Attendance;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\Schedule;
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
        $this->date  = now()->toDateString();
        $coach       = Auth::user()->coach;
        $today       = strtolower(now()->format('l'));

        if (!$coach) return;

        // Prefer a schedule the coach has already checked into today
        $checkedInScheduleId = CoachSession::where('coach_id', $coach->id)
            ->whereDate('session_date', today())
            ->value('schedule_id');

        if ($checkedInScheduleId) {
            $this->scheduleId = $checkedInScheduleId;
        } else {
            // Fall back to an assigned private schedule for today
            $first = Schedule::where('type', 'private')
                ->where('coach_id', $coach->id)
                ->where('is_active', true)
                ->where('day_of_week', $today)
                ->first();
            if ($first) $this->scheduleId = $first->id;
        }

        if ($this->scheduleId) $this->loadRoster();
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

        $this->authorizeCoach();

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
        if (!$this->scheduleId || empty($this->roster)) return;

        $this->authorizeCoach();

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

    private function authorizeCoach(): void
    {
        $coach = Auth::user()->coach;
        if (!$coach) abort(403);

        $schedule = Schedule::find($this->scheduleId);
        if (!$schedule) abort(403);

        if ($schedule->type === 'private') {
            // Private: must be the assigned coach
            if ($schedule->coach_id !== $coach->id) abort(403);
        } else {
            // Regular: must have checked in to this schedule today
            $hasSession = CoachSession::where('schedule_id', $this->scheduleId)
                ->where('coach_id', $coach->id)
                ->whereDate('session_date', today())
                ->exists();
            if (!$hasSession) abort(403);
        }
    }

    public function render()
    {
        $coach = Auth::user()->coach;
        $today = strtolower(now()->format('l'));

        // Build the list of schedules this coach can manage attendance for
        $schedules = $coach
            ? Schedule::with(['location', 'program'])
                ->where('is_active', true)
                ->where(function ($q) use ($coach, $today) {
                    // Private sessions assigned to this coach
                    $q->where(function ($q) use ($coach, $today) {
                        $q->where('type', 'private')
                          ->where('coach_id', $coach->id)
                          ->where('day_of_week', $today);
                    })
                    // Regular sessions checked into today
                    ->orWhereHas('coachSessions', fn($s) =>
                        $s->where('coach_id', $coach->id)
                          ->whereDate('session_date', today())
                    );
                })
                ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
                ->orderBy('start_time')
                ->get()
            : collect();

        return view('livewire.coach.take-attendance', compact('schedules'));
    }
}
