<?php

namespace App\Livewire\Coach;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QrScanner extends Component
{
    public int|string $scheduleId = '';
    public string $scanDate       = '';
    public bool $scannerActive = false;

    // GPS coords (set by browser before scanning / marking)
    public ?float  $latitude  = null;
    public ?float  $longitude = null;

    // Last scan result
    public ?string $lastScanName    = null;
    public ?string $lastScanStatus  = null; // 'success' | 'duplicate' | 'not_found' | 'not_enrolled'
    public ?string $lastScanMessage = null;

    public function mount(): void
    {
        $this->scanDate = now()->toDateString();
    }

    public function updatedScanDate(): void
    {
        // Reset schedule & scanner when date changes — day of week may differ
        $this->scheduleId   = '';
        $this->scannerActive = false;
        $this->resetScanResult();
    }

    public function updatedScheduleId(): void
    {
        $this->scannerActive = false;
        $this->resetScanResult();
    }

    public function activateScanner(): void
    {
        // Guard first — before any form validation
        $coach = Auth::user()->coach;
        $hasCheckedIn = $coach && \App\Models\CoachSession::where('coach_id', $coach->id)
            ->whereDate('session_date', today())
            ->exists();

        if (!$hasCheckedIn) {
            $this->dispatch('show-checkin-warning');
            return;
        }

        $this->validate([
            'scheduleId' => 'required|integer',
            'scanDate'   => 'required|date',
        ]);

        $this->authorizeOwnsSchedule();
        $this->scannerActive = true;
        $this->resetScanResult();
    }

    public function deactivateScanner(): void
    {
        $this->scannerActive = false;
    }

    public function markPresent(int $childId): void
    {
        if (!$this->scheduleId || !$this->scanDate) return;

        $this->authorizeOwnsSchedule();

        $enrollment = Enrollment::where('child_id', $childId)
            ->where('schedule_id', $this->scheduleId)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->first();

        if (!$enrollment) return;

        $already = Attendance::where('child_id', $childId)
            ->where('schedule_id', $this->scheduleId)
            ->whereDate('attended_at', $this->scanDate)
            ->exists();

        if ($already) return;

        $coach = Auth::user()->coach;

        Attendance::create([
            'child_id'      => $childId,
            'enrollment_id' => $enrollment->id,
            'schedule_id'   => $this->scheduleId,
            'coach_id'      => $coach?->id,
            'attended_at'   => $this->scanDate,
            'status'        => 'present',
            'source'        => 'manual',
            'ip_address'    => request()->ip(),
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
        ]);
    }

    public function undoPresent(int $childId): void
    {
        if (!$this->scheduleId || !$this->scanDate) return;

        $this->authorizeOwnsSchedule();

        Attendance::where('child_id', $childId)
            ->where('schedule_id', $this->scheduleId)
            ->whereDate('attended_at', $this->scanDate)
            ->where('source', 'manual')
            ->delete();
    }

    public function processQr(string $qrValue): void
    {
        $this->resetScanResult();

        $child = Child::where('qr_identifier', trim($qrValue))->first();

        if (!$child) {
            $this->lastScanStatus  = 'not_found';
            $this->lastScanMessage = 'QR code not recognised.';
            return;
        }

        $this->lastScanName = $child->name;

        $enrollment = $child->enrollments()
            ->where('schedule_id', $this->scheduleId)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->first();

        if (!$enrollment) {
            $this->lastScanStatus  = 'not_enrolled';
            $this->lastScanMessage = "{$child->name} is not enrolled in this schedule.";
            return;
        }

        $existing = Attendance::where('child_id', $child->id)
            ->where('schedule_id', $this->scheduleId)
            ->whereDate('attended_at', $this->scanDate)
            ->first();

        if ($existing) {
            $this->lastScanStatus  = 'duplicate';
            $this->lastScanMessage = "{$child->name} is already marked present.";
            return;
        }

        $coach = Auth::user()->coach;

        Attendance::create([
            'child_id'      => $child->id,
            'enrollment_id' => $enrollment->id,
            'schedule_id'   => $this->scheduleId,
            'coach_id'      => $coach?->id,
            'attended_at'   => $this->scanDate,
            'status'        => 'present',
            'source'        => 'qr',
            'ip_address'    => request()->ip(),
            'latitude'      => $this->latitude,
            'longitude'     => $this->longitude,
        ]);

        $this->lastScanStatus  = 'success';
        $this->lastScanMessage = "{$child->name} marked present successfully.";
    }

    private function resetScanResult(): void
    {
        $this->lastScanName    = null;
        $this->lastScanStatus  = null;
        $this->lastScanMessage = null;
    }

    private function authorizeOwnsSchedule(): void
    {
        $coach = Auth::user()->coach;
        if (!$coach) abort(403);

        $schedule = Schedule::find($this->scheduleId);
        if (!$schedule || !$schedule->is_active) abort(403);

        if ($schedule->type === 'private' && $schedule->coach_id !== $coach->id) {
            abort(403);
        }
    }

    public function render()
    {
        $coach = Auth::user()->coach;

        // Derive day of week from selected date to filter the schedule dropdown
        $dayOfWeek = $this->scanDate
            ? strtolower(date('l', strtotime($this->scanDate)))
            : null;

        $schedules = Schedule::where('is_active', true)
            ->whereHas('enrollments', fn($q) => $q->where('status', 'approved')->where('type', 'program'))
            ->when($dayOfWeek, fn($q) => $q->where('day_of_week', $dayOfWeek))
            ->where(function ($q) use ($coach) {
                $q->where('type', 'regular')
                  ->orWhere(function ($q2) use ($coach) {
                      $q2->where('type', 'private')->where('coach_id', $coach?->id);
                  });
            })
            ->with(['program', 'location'])
            ->orderBy('start_time')
            ->get();

        // Build full roster with live status for the selected schedule + date
        $roster = collect();
        $presentCount = 0;

        if ($this->scheduleId && $this->scanDate) {
            // All approved program enrollments for this schedule
            $enrollments = Enrollment::where('schedule_id', $this->scheduleId)
                ->where('status', 'approved')
                ->where('type', 'program')
                ->with('child')
                ->get();

            // Keyed by child_id
            $attendances = Attendance::where('schedule_id', $this->scheduleId)
                ->whereDate('attended_at', $this->scanDate)
                ->pluck('status', 'child_id');

            $leaveRequests = LeaveRequest::where('schedule_id', $this->scheduleId)
                ->where('leave_date', $this->scanDate)
                ->whereIn('status', ['approved', 'auto_approved', 'pending'])
                ->pluck('type', 'child_id'); // 'sick' | 'permit'

            foreach ($enrollments as $enrollment) {
                $childId = $enrollment->child_id;

                if (isset($attendances[$childId])) {
                    $status = 'present';
                    $presentCount++;
                } elseif (isset($leaveRequests[$childId])) {
                    $status = $leaveRequests[$childId]; // 'sick' or 'permit'
                } else {
                    $status = 'not_yet';
                }

                $roster->push([
                    'child'         => $enrollment->child,
                    'enrollment_id' => $enrollment->id,
                    'status'        => $status,
                ]);
            }

            // Sort: present first, then leave, then not_yet
            $order = ['present' => 0, 'sick' => 1, 'permit' => 1, 'not_yet' => 2];
            $roster = $roster->sortBy(fn($r) => $order[$r['status']] ?? 3)->values();
        }

        return view('livewire.coach.qr-scanner', compact('schedules', 'roster', 'presentCount'));
    }
}
