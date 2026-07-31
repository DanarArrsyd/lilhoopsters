<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LeaveRequests extends Component
{
    public string $filterChildId = '';

    public bool   $showForm        = false;
    public int    $step            = 1;
    public int    $totalSteps      = 3;
    public ?int   $selectedChildId = null;
    public ?int   $enrollmentId    = null;
    public string $leaveDate       = '';
    public string $type            = '';
    public string $reason          = '';

    public function updatedSelectedChildId(): void
    {
        $this->enrollmentId = null;
    }

    public function selectFilterChild(int $childId): void
    {
        $this->filterChildId = (string) $childId;
    }

    public function openCreate(): void
    {
        $this->step            = 1;
        $this->selectedChildId = null;
        $this->enrollmentId    = null;
        $this->leaveDate       = '';
        $this->type            = '';
        $this->reason          = '';
        $this->resetErrorBag();
        $this->showForm        = true;
    }

    public function cancel(): void
    {
        $this->showForm = false;
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(['selectedChildId' => 'required|integer']);
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            $this->validate([
                'enrollmentId' => 'required|integer',
                'leaveDate'    => 'required|date|after_or_equal:' . now()->subDays(7)->toDateString() . '|before_or_equal:today',
                'type'         => 'required|in:sick,permit',
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
            'selectedChildId' => 'required|integer',
            'enrollmentId'    => 'required|integer',
            'leaveDate'       => 'required|date|after_or_equal:' . now()->subDays(7)->toDateString() . '|before_or_equal:today',
            'type'            => 'required|in:sick,permit',
            'reason'          => 'nullable|string|max:500',
        ]);

        $childIds   = Auth::user()->children()->pluck('id');
        $enrollment = Enrollment::whereIn('child_id', $childIds)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id')
            ->findOrFail($this->enrollmentId);

        $exists = LeaveRequest::where('enrollment_id', $enrollment->id)
            ->where('leave_date', $this->leaveDate)
            ->exists();

        if ($exists) {
            $this->step = 2;
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

        NotificationService::toAdmins(
            'new_leave_request',
            'New Leave Request',
            Auth::user()->name . " submitted a leave request for " . now()->parse($this->leaveDate)->format('d M Y') . ". Please review.",
        );

        session()->flash('success', 'Leave request submitted.');
        $this->showForm = false;
    }

    public function mount(): void
    {
        $user  = Auth::user();
        $first = $user->children()->where('status', 'active')->orderBy('name')->first();
        if ($first) {
            $this->filterChildId = (string) $first->id;
        }

        // Arriving from a calendar day: open the form on that date instead of
        // making the parent pick it again. Guarded by the same window the
        // validation enforces, so a link can't land on a date the form rejects.
        $date = request()->query('date');

        if ($first && $date && $this->isRequestableDate($date)) {
            $this->openCreate();
            $this->selectedChildId = $first->id;
            $this->leaveDate       = Carbon::parse($date)->toDateString();
            $this->step            = 2;
        }
    }

    /** Leave is reported for a session that already happened: today back to 7 days. */
    public static function isRequestableDate(?string $date): bool
    {
        if (! $date) {
            return false;
        }

        try {
            $day = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return false;
        }

        return $day->betweenIncluded(now()->subDays(7)->startOfDay(), now()->startOfDay());
    }

    public function render()
    {
        $user     = Auth::user();
        $children = $user->children()->where('status', 'active')->orderBy('name')->get();
        $childIds = $children->pluck('id');

        $enrollmentsByChild = $this->selectedChildId
            ? Enrollment::where('child_id', $this->selectedChildId)
                ->where('status', 'approved')
                ->where('type', 'program')
                ->whereNotNull('schedule_id')
                ->with(['schedule.program', 'schedule.location'])
                ->get()
            : collect();

        $hasProgramEnrollments = Enrollment::whereIn('child_id', $childIds)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id')
            ->exists();

        $leaveRequests = LeaveRequest::whereIn('child_id', $childIds)
            ->with(['child', 'schedule.program', 'schedule.location'])
            ->when($this->filterChildId, fn($q) => $q->where('child_id', $this->filterChildId))
            ->orderBy('leave_date', 'desc')
            ->get();

        // Selected enrollment for step 3 summary
        $selectedEnrollment = $this->enrollmentId
            ? $enrollmentsByChild->firstWhere('id', $this->enrollmentId)
            : null;

        return view('livewire.portal.leave-requests', compact(
            'enrollmentsByChild', 'hasProgramEnrollments', 'leaveRequests',
            'children', 'selectedEnrollment'
        ))->layout('components.app', ['title' => 'Leave Requests']);
    }
}
