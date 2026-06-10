<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveRequests extends Component
{
    use WithPagination;

    public string $filterChildId = '';
    public string $filterStatus  = '';

    public bool   $showForm      = false;
    public ?int   $enrollmentId  = null;
    public string $leaveDate     = '';
    public string $type          = '';
    public string $reason        = '';

    public function updatingFilterChildId(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void  { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->enrollmentId = null;
        $this->leaveDate    = '';
        $this->type         = '';
        $this->reason       = '';
        $this->showForm     = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
    }

    public function submit(): void
    {
        $this->validate([
            'enrollmentId' => 'required|integer',
            'leaveDate'    => 'required|date|after_or_equal:today',
            'type'         => 'required|in:sick,permit',
            'reason'       => 'nullable|string|max:500',
        ]);

        $childIds  = Auth::user()->children()->pluck('id');
        $enrollment = Enrollment::whereIn('child_id', $childIds)
            ->where('status', 'approved')
            ->whereNotNull('schedule_id')
            ->findOrFail($this->enrollmentId);

        // Prevent duplicate leave request for same date + enrollment
        $exists = LeaveRequest::where('enrollment_id', $enrollment->id)
            ->where('leave_date', $this->leaveDate)
            ->exists();

        if ($exists) {
            $this->addError('leaveDate', 'A leave request for this date already exists.');
            return;
        }

        LeaveRequest::create([
            'child_id'      => $enrollment->child_id,
            'enrollment_id' => $enrollment->id,
            'schedule_id'   => $enrollment->schedule_id,
            'leave_date'    => $this->leaveDate,
            'type'          => $this->type,
            'reason'        => $this->reason ?: null,
        ]);

        session()->flash('success', 'Leave request submitted.');
        $this->showForm = false;
        $this->resetPage();
    }

    public function render()
    {
        $user     = Auth::user();
        $childIds = $user->children()->pluck('id');

        $activeEnrollments = Enrollment::whereIn('child_id', $childIds)
            ->where('status', 'approved')
            ->whereNotNull('schedule_id')
            ->with(['child', 'schedule.program', 'schedule.location'])
            ->get();

        $leaveRequests = LeaveRequest::whereIn('child_id', $childIds)
            ->with(['child', 'enrollment.package', 'schedule.program', 'schedule.location'])
            ->when($this->filterChildId, fn($q) => $q->where('child_id', $this->filterChildId))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->orderBy('leave_date', 'desc')
            ->paginate(15);

        $children = $user->children()->where('status', 'active')->orderBy('name')->get();

        return view('livewire.portal.leave-requests', compact(
            'activeEnrollments', 'leaveRequests', 'children'
        ));
    }
}
