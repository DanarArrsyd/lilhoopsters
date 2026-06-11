<?php

namespace App\Livewire\Coach;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class QrScanner extends Component
{
    public int|string $scheduleId = '';
    public string $scanDate       = '';
    public bool $scannerActive    = false;

    // Last scan result
    public ?string $lastScanName    = null;
    public ?string $lastScanStatus  = null; // 'success' | 'duplicate' | 'not_found' | 'not_enrolled'
    public ?string $lastScanMessage = null;

    public function mount(): void
    {
        $this->scanDate = now()->toDateString();
    }

    public function activateScanner(): void
    {
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

    public function processQr(string $qrValue): void
    {
        $this->resetScanResult();

        $child = Child::where('qr_identifier', trim($qrValue))->first();

        if (!$child) {
            $this->lastScanStatus  = 'not_found';
            $this->lastScanMessage = 'QR code tidak dikenali.';
            return;
        }

        $this->lastScanName = $child->name;

        // Check child is enrolled in this schedule (approved enrollment)
        $enrollment = $child->enrollments()
            ->where('schedule_id', $this->scheduleId)
            ->where('status', 'approved')
            ->first();

        if (!$enrollment) {
            $this->lastScanStatus  = 'not_enrolled';
            $this->lastScanMessage = "{$child->name} tidak terdaftar di jadwal ini.";
            return;
        }

        // Check for duplicate attendance
        $existing = Attendance::where('child_id', $child->id)
            ->where('schedule_id', $this->scheduleId)
            ->whereDate('attended_at', $this->scanDate)
            ->first();

        if ($existing) {
            $this->lastScanStatus  = 'duplicate';
            $this->lastScanMessage = "{$child->name} sudah tercatat hadir.";
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
        ]);

        $this->lastScanStatus  = 'success';
        $this->lastScanMessage = "{$child->name} berhasil dicatat hadir.";
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

        $owns = $coach->schedules()->where('id', $this->scheduleId)->exists();
        if (!$owns) abort(403);
    }

    public function render()
    {
        $coach = Auth::user()->coach;

        $schedules = Schedule::where('is_active', true)
            ->whereHas('enrollments', fn($q) => $q->where('status', 'approved'))
            ->where('coach_id', $coach?->id)
            ->with(['program', 'location'])
            ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->get();

        $todayAttendances = [];
        if ($this->scheduleId && $this->scanDate) {
            $todayAttendances = Attendance::where('schedule_id', $this->scheduleId)
                ->whereDate('attended_at', $this->scanDate)
                ->where('status', 'present')
                ->with('child')
                ->get();
        }

        return view('livewire.coach.qr-scanner', compact('schedules', 'todayAttendances'));
    }
}
