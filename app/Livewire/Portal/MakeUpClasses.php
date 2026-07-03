<?php

namespace App\Livewire\Portal;

use App\Models\LeaveRequest;
use App\Models\MakeUpClass;
use App\Models\Schedule;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MakeUpClasses extends Component
{
    use WithPagination;

    public bool $showForm = false;
    public int $step      = 1;
    public int $totalSteps = 3;

    public int|string $leaveRequestId   = '';
    public int|string $targetScheduleId = '';
    public string $targetDate           = '';

    public function openForm(): void
    {
        $this->step             = 1;
        $this->leaveRequestId   = '';
        $this->targetScheduleId = '';
        $this->targetDate       = '';
        $this->resetErrorBag();
        $this->showForm         = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(['leaveRequestId' => 'required|integer']);
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $this->validate([
                'targetScheduleId' => 'required|exists:schedules,id',
                'targetDate'       => 'required|date|after_or_equal:today',
            ]);
            $this->step = 3;
        }
    }

    public function prevStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function back(): void
    {
        $this->prevStep();
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
            $this->step = 1;
            $this->addError('leaveRequestId', 'Leave request not found.');
            return;
        }

        $exists = MakeUpClass::where('leave_request_id', $leaveRequest->id)->exists();
        if ($exists) {
            $this->step = 1;
            $this->addError('leaveRequestId', 'Make-up class already requested for this leave.');
            return;
        }

        // The replacement must be for the same program as the missed session.
        $targetSchedule = Schedule::find($this->targetScheduleId);
        if (! $targetSchedule || $targetSchedule->program_id !== $leaveRequest->schedule?->program_id) {
            $this->step = 2;
            $this->addError('targetScheduleId', 'Selected schedule does not match the missed program.');
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

        session()->flash('success', 'Make-up class requested.');
        $this->showForm = false;
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
            ->with(['child', 'schedule.program'])
            ->get();

        $selectedLeave = $this->leaveRequestId
            ? $approvedLeaves->firstWhere('id', (int) $this->leaveRequestId)
            : null;

        // Only offer replacement schedules for the SAME program as the missed
        // session — a make-up must stay within the child's enrolled program.
        $programId = $selectedLeave?->schedule?->program_id;

        $schedules = $programId
            ? Schedule::where('is_active', true)
                ->where('program_id', $programId)
                ->with(['program', 'location'])
                ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
                ->get()
            : collect();

        $selectedSchedule = $this->targetScheduleId
            ? $schedules->firstWhere('id', (int) $this->targetScheduleId)
            : null;

        return view('livewire.portal.makeup-classes', compact(
            'makeUpClasses', 'approvedLeaves', 'schedules', 'selectedLeave', 'selectedSchedule'
        ))->layout('components.app', ['title' => 'Make-Up Classes']);
    }
}
