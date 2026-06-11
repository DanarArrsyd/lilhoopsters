<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\MakeUpClass;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MakeUpClasses extends Component
{
    use WithPagination;

    public bool $showForm       = false;
    public int|string $leaveRequestId  = '';
    public int|string $targetScheduleId = '';
    public string $targetDate   = '';

    public function openForm(): void
    {
        $this->leaveRequestId   = '';
        $this->targetScheduleId = '';
        $this->targetDate       = '';
        $this->showForm         = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function submit(): void
    {
        $this->validate([
            'leaveRequestId'   => 'required|integer',
            'targetScheduleId' => 'required|exists:schedules,id',
            'targetDate'       => 'required|date|after_or_equal:today',
        ]);

        $childIds = Auth::user()->children()->pluck('id');

        $leaveRequest = LeaveRequest::whereIn('child_id', $childIds)
            ->whereIn('status', ['approved', 'auto_approved'])
            ->find($this->leaveRequestId);

        if (!$leaveRequest) {
            $this->addError('leaveRequestId', 'Leave request not found.');
            return;
        }

        // Check not already requested
        $exists = MakeUpClass::where('leave_request_id', $leaveRequest->id)->exists();
        if ($exists) {
            $this->addError('leaveRequestId', 'Make-up class already requested for this leave.');
            return;
        }

        MakeUpClass::create([
            'child_id'           => $leaveRequest->child_id,
            'enrollment_id'      => $leaveRequest->enrollment_id,
            'leave_request_id'   => $leaveRequest->id,
            'target_schedule_id' => $this->targetScheduleId,
            'target_date'        => $this->targetDate,
            'status'             => 'pending',
        ]);

        $this->showForm = false;
        session()->flash('success', 'Make-up class requested.');
    }

    public function render()
    {
        $childIds = Auth::user()->children()->pluck('id');

        $makeUpClasses = MakeUpClass::whereIn('child_id', $childIds)
            ->with(['child', 'targetSchedule.program', 'targetSchedule.location', 'leaveRequest'])
            ->latest()
            ->paginate(10);

        $approvedLeaves = LeaveRequest::whereIn('child_id', $childIds)
            ->whereIn('status', ['approved', 'auto_approved'])
            ->whereDoesntHave('makeUpClass')
            ->with('child')
            ->get();

        $schedules = Schedule::where('is_active', true)
            ->with(['program', 'location'])
            ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->get();

        return view('livewire.portal.makeup-classes', compact('makeUpClasses', 'approvedLeaves', 'schedules'));
    }
}
