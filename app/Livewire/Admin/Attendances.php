<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\CoachSession;
use App\Models\Schedule;
use Livewire\Component;
use Livewire\WithPagination;

class Attendances extends Component
{
    use WithPagination;

    public string $activeTab    = 'students';
    public string $search      = '';
    public string $filterDateFrom = '';
    public string $filterDateTo   = '';
    public string $filterStatus = '';
    public int|string $filterSchedule = '';

    // Override modal
    public bool $showOverride    = false;
    public ?int $overrideId      = null;
    public string $overrideStatus = '';
    public string $overrideNotes  = '';

    public function updatedActiveTab(): void    { $this->resetPage(); $this->search = ''; }
    public function updatedSearch(): void       { $this->resetPage(); }
    public function updatedFilterDateFrom(): void { $this->resetPage(); }
    public function updatedFilterDateTo(): void   { $this->resetPage(); }
    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedFilterSchedule(): void { $this->resetPage(); }

    public function openOverride(int $id): void
    {
        $record = Attendance::findOrFail($id);
        $this->overrideId     = $id;
        $this->overrideStatus = $record->status;
        $this->overrideNotes  = $record->notes ?? '';
        $this->showOverride   = true;
    }

    public function saveOverride(): void
    {
        $this->validate([
            'overrideStatus' => 'required|in:present,no_show,sick,permit,make_up',
            'overrideNotes'  => 'nullable|string|max:500',
        ]);

        $attendance = Attendance::findOrFail($this->overrideId);
        $oldStatus  = $attendance->status;

        $attendance->update([
            'status' => $this->overrideStatus,
            'notes'  => $this->overrideNotes ?: null,
            'source' => 'manual',
        ]);

        AuditLog::record(
            'attendance.overridden',
            $attendance,
            'Overrode attendance for ' . ($attendance->child?->name ?? 'unknown child') . " to {$this->overrideStatus}",
            ['old_status' => $oldStatus, 'new_status' => $this->overrideStatus],
        );

        $this->showOverride = false;
        $this->overrideId   = null;
        session()->flash('success', 'Attendance updated.');
    }

    public function closeOverride(): void
    {
        $this->showOverride = false;
        $this->overrideId   = null;
    }

    public function render()
    {
        $schedules = Schedule::where('is_active', true)->with(['program', 'location'])->get();

        if ($this->activeTab === 'coaches') {
            // Close any sessions left open past their scheduled end time.
            CoachSession::autoCloseStale();

            $coachSessions = CoachSession::with(['coach.user', 'schedule.program', 'schedule.location'])
                ->when($this->search, fn($q) =>
                    $q->whereHas('coach.user', fn($u) => $u->where('name', 'like', "%{$this->search}%"))
                )
                ->when($this->filterDateFrom, fn($q) => $q->whereDate('session_date', '>=', $this->filterDateFrom))
                ->when($this->filterDateTo,   fn($q) => $q->whereDate('session_date', '<=', $this->filterDateTo))
                ->when($this->filterSchedule, fn($q) => $q->where('schedule_id', $this->filterSchedule))
                ->latest('session_date')
                ->paginate(20);

            return view('livewire.admin.attendances', [
                'attendances'   => null,
                'coachSessions' => $coachSessions,
                'stats'         => [],
                'schedules'     => $schedules,
            ]);
        }

        $query = Attendance::with(['child', 'schedule.program', 'schedule.location', 'coach.user'])
            ->when($this->search, function ($q) {
                $q->whereHas('child', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterSchedule, fn($q) => $q->where('schedule_id', $this->filterSchedule))
            ->latest('attended_at');

        $dateScope = fn ($q) => $q
            ->when($this->filterDateFrom, fn($q) => $q->whereDate('attended_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo,   fn($q) => $q->whereDate('attended_at', '<=', $this->filterDateTo));

        $stats = [
            'total'   => $dateScope(Attendance::query())->count(),
            'present' => $dateScope(Attendance::query())->where('status', 'present')->count(),
            'absent'  => $dateScope(Attendance::query())->where('status', 'no_show')->count(),
            'sick'    => $dateScope(Attendance::query())->whereIn('status', ['sick', 'permit'])->count(),
        ];

        return view('livewire.admin.attendances', [
            'attendances'   => $query->paginate(20),
            'coachSessions' => null,
            'stats'         => $stats,
            'schedules'     => $schedules,
        ]);
    }
}
