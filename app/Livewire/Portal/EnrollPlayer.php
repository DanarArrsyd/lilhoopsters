<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class EnrollPlayer extends Component
{
    public int    $step           = 1;
    public int    $totalSteps     = 5;
    public string $enrollmentType = ''; // 'registration' or 'program'

    public ?int    $selectedChildId    = null;
    public ?string $selectedDay        = null;
    public ?int    $selectedLocationId = null;
    public ?int    $selectedScheduleId = null;
    public ?int    $selectedPackageId  = null;
    public string  $startDate          = '';
    public string  $jerseyName         = '';
    public string  $jerseyNumber       = '';
    public string  $memberNotes        = '';
    public string  $previewTrxCode     = '';

    public function selectChild(int $childId): void
    {
        $child = Auth::user()->children()->findOrFail($childId);

        if ($child->status === 'unregistered') {
            $this->enrollmentType = 'registration';
        } elseif ($child->status === 'active') {
            $this->enrollmentType = 'program';
        } else {
            return;
        }

        $this->selectedChildId = $childId;
        $this->step = 2;
    }

    public function selectDay(string $day): void
    {
        $this->selectedDay        = $day;
        $this->selectedLocationId = null;
        $this->selectedScheduleId = null;
    }

    public function selectLocation(int $locationId): void
    {
        $this->selectedLocationId = $locationId;
        $this->selectedScheduleId = null;
        $this->step = 3;
    }

    public function selectSchedule(int $scheduleId): void
    {
        Schedule::where('is_active', true)->findOrFail($scheduleId);
        $this->selectedScheduleId = $scheduleId;
        $this->step = 4;
    }

    public function confirmDetails(): void
    {
        $pkg = $this->selectedPackageId
            ? Package::find($this->selectedPackageId)
            : null;

        $this->validate([
            'selectedPackageId' => 'required|exists:packages,id',
            'startDate'         => ($pkg?->type === 'regular') ? 'required|date|after_or_equal:today' : 'nullable|date',
            'jerseyName'        => 'nullable|string|max:50',
            'jerseyNumber'      => 'nullable|string|max:10',
            'memberNotes'       => 'nullable|string|max:500',
        ], [
            'startDate.required'          => 'Please choose a start date.',
            'startDate.after_or_equal'    => 'Start date must be today or in the future.',
        ]);

        $this->previewTrxCode = 'TRX-' . strtoupper(Str::random(8));
        $this->step = 5;
    }

    public function back(): void
    {
        if ($this->step <= 1) return;

        $this->step--;

        match ($this->step) {
            1 => $this->reset(['enrollmentType', 'selectedChildId', 'selectedDay', 'selectedLocationId',
                               'selectedScheduleId', 'selectedPackageId', 'startDate', 'jerseyName', 'jerseyNumber', 'memberNotes', 'previewTrxCode']),
            2 => $this->reset(['selectedLocationId', 'selectedScheduleId', 'selectedPackageId',
                               'startDate', 'jerseyName', 'jerseyNumber', 'memberNotes', 'previewTrxCode']),
            3 => $this->reset(['selectedScheduleId', 'selectedPackageId', 'startDate', 'jerseyName', 'jerseyNumber', 'memberNotes', 'previewTrxCode']),
            4 => $this->reset(['previewTrxCode']),
            default => null,
        };
    }

    public function submit(): void
    {
        $this->validate([
            'selectedPackageId' => 'required|exists:packages,id',
        ]);

        $child   = Auth::user()->children()->findOrFail($this->selectedChildId);
        $package = Package::where('is_active', true)->findOrFail($this->selectedPackageId);

        DB::transaction(function () use ($child, $package) {
            $transaction = Transaction::create([
                'user_id'          => Auth::id(),
                'child_id'         => $child->id,
                'package_id'       => $package->id,
                'amount'           => $package->price,
                'status'           => 'pending',
                'transaction_code' => $this->previewTrxCode ?: null,
            ]);

            $startedAt  = ($package->type === 'regular' && $this->startDate)
                ? Carbon::parse($this->startDate)
                : null;
            $expiresAt  = ($startedAt && $package->validity_days)
                ? $startedAt->copy()->addDays($package->validity_days - 1)
                : null;

            $enrollment = Enrollment::create([
                'child_id'       => $child->id,
                'type'           => $this->enrollmentType,
                'schedule_id'    => $this->selectedScheduleId,
                'package_id'     => $package->id,
                'transaction_id' => $transaction->id,
                'status'         => 'pending',
                'member_notes'   => $this->memberNotes ?: null,
                'started_at'     => $startedAt,
                'expires_at'     => $expiresAt,
                'total_sessions' => $package->session_count,
                'remaining_sessions' => $package->session_count,
            ]);

            $transaction->update(['enrollment_id' => $enrollment->id]);

            if ($this->enrollmentType === 'registration') {
                $child->update([
                    'status'        => 'pending',
                    'jersey_name'   => $this->jerseyName ?: null,
                    'jersey_number' => $this->jerseyNumber ?: null,
                ]);
            }
        });

        NotificationService::toAdmins(
            'new_enrollment',
            'New Enrollment Submitted',
            Auth::user()->name . " has submitted a new enrollment for {$child->name}. Please review.",
        );

        session()->flash('success', 'Enrollment submitted! Please upload your payment proof.');
        $this->redirect(route('parent.payments'), navigate: true);
    }

    public function render()
    {
        $user = Auth::user();

        $enrollableChildren = $user->children()
            ->whereIn('status', ['unregistered', 'active'])
            ->orderBy('name')
            ->get();

        $availableDays = Schedule::where('is_active', true)
            ->where('type', '!=', 'private')
            ->distinct()
            ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->pluck('day_of_week');

        $availableLocations = $this->selectedDay
            ? Location::where('is_active', true)
                ->whereHas('schedules', fn($q) => $q->where('is_active', true)->where('type', '!=', 'private')->where('day_of_week', $this->selectedDay))
                ->orderBy('name')
                ->get()
            : collect();

        $selectedLocation = $this->selectedLocationId
            ? Location::find($this->selectedLocationId)
            : null;

        $availableSchedules = ($this->selectedDay && $this->selectedLocationId)
            ? Schedule::with(['program', 'coach.user'])
                ->where('is_active', true)
                ->where('type', '!=', 'private')
                ->where('day_of_week', $this->selectedDay)
                ->where('location_id', $this->selectedLocationId)
                ->orderBy('start_time')
                ->get()
            : collect();

        $selectedSchedule = $this->selectedScheduleId
            ? Schedule::with(['program', 'location', 'coach.user'])->find($this->selectedScheduleId)
            : null;

        $availablePackages = $this->selectedLocationId
            ? Package::where('is_active', true)
                ->where('location_id', $this->selectedLocationId)
                ->when(
                    $this->enrollmentType === 'registration',
                    fn($q) => $q->where('type', 'registration'),
                    fn($q) => $q->whereIn('type', ['regular', 'drop_in'])
                )
                ->get()
            : collect();

        return view('livewire.portal.enroll-player', compact(
            'enrollableChildren', 'availableDays', 'availableLocations', 'selectedLocation',
            'availableSchedules', 'selectedSchedule', 'availablePackages'
        ))->layout('components.app', ['title' => 'Enroll Player']);
    }
}
