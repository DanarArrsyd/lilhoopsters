<?php

namespace App\Livewire\Coach;

use App\Models\Attendance;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
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
            ->active()
            ->where('type', 'program')
            ->with('child')
            ->get();

        $existing = Attendance::where('schedule_id', $this->scheduleId)
            ->whereDate('attended_at', $this->date)
            ->pluck('status', 'child_id');

        // Surface an excused leave so the coach sees it before marking
        // anything — QrScanner already does this; this page never did.
        $leaveRequests = LeaveRequest::where('schedule_id', $this->scheduleId)
            ->where('leave_date', $this->date)
            ->whereIn('status', ['approved', 'auto_approved', 'pending'])
            ->pluck('type', 'child_id'); // 'sick' | 'permit'

        $roster = $enrollments->map(fn($e) => [
            'child_id'         => $e->child_id,
            'enrollment_id'    => $e->id,
            'make_up_class_id' => null,
            'name'             => $e->child->name,
            // No default status: a coach must explicitly mark each child —
            // silently defaulting everyone to "present" let an untouched
            // roster burn a session for a kid who never showed up.
            'status'           => $existing[$e->child_id] ?? $leaveRequests[$e->child_id] ?? null,
        ]);

        // Children booked for an approved make-up class on this exact
        // schedule + date — they aren't enrolled here, but they're expected.
        $makeUpClasses = \App\Models\MakeUpClass::where('target_schedule_id', $this->scheduleId)
            ->whereDate('target_date', $this->date)
            ->where('status', 'approved')
            ->with('child')
            ->get()
            ->map(fn($m) => [
                'child_id'         => $m->child_id,
                'enrollment_id'    => $m->enrollment_id,
                'make_up_class_id' => $m->id,
                'name'             => $m->child->name,
                'status'           => $existing[$m->child_id] ?? null,
            ]);

        $this->roster = $roster->concat($makeUpClasses)->toArray();
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
        $skipped = 0;

        foreach ($this->roster as $row) {
            $isMakeUp = !empty($row['make_up_class_id']);

            if (empty($row['status'])) {
                $skipped++;
                continue;
            }

            // A make-up-booked child has only one meaningful outcome here:
            // they showed up for it. Any other button click is a no-op for
            // this row — there's no defined "no_show" for a make-up slot.
            if ($isMakeUp && $row['status'] !== 'present') {
                $skipped++;
                continue;
            }

            // An excused absence must not be overridden into a session-burning
            // no_show — the coach can still record sick/permit here, just not
            // no_show for the same child+date.
            if ($row['status'] === 'no_show' && $this->hasExcusedLeave($row['child_id'])) {
                $skipped++;
                continue;
            }

            Attendance::updateOrCreate(
                [
                    'child_id'    => $row['child_id'],
                    'schedule_id' => $this->scheduleId,
                    'attended_at' => $this->date,
                ],
                [
                    'enrollment_id'    => $row['enrollment_id'],
                    'coach_id'         => $coach->id,
                    'make_up_class_id' => $row['make_up_class_id'],
                    'status'           => $isMakeUp ? 'make_up' : $row['status'],
                    'source'           => 'manual',
                    'ip_address'       => request()->ip(),
                ]
            );
        }

        $this->saved = true;
        session()->flash(
            'attendance_success',
            $skipped > 0
                ? "Attendance saved. {$skipped} student(s) left unmarked — they were not recorded."
                : 'Attendance saved.'
        );
    }

    /** Does this child have an excused (approved/auto_approved/pending) leave for this schedule+date? */
    private function hasExcusedLeave(int $childId): bool
    {
        return LeaveRequest::where('child_id', $childId)
            ->where('schedule_id', $this->scheduleId)
            ->where('leave_date', $this->date)
            ->whereIn('status', ['approved', 'auto_approved', 'pending'])
            ->exists();
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
