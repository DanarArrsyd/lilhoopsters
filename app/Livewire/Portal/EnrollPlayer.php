<?php

namespace App\Livewire\Portal;

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Schedule;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class EnrollPlayer extends Component
{
    public int    $step           = 1;
    public string $enrollmentType = ''; // 'registration' or 'program'

    // Step 1
    public ?int $selectedChildId = null;

    // Step 2 — registration
    public ?int $selectedLocationId = null;

    // Step 2 — program (schedule browser)
    public string $filterDay        = '';
    public string $filterLocationId = '';
    public ?int   $selectedScheduleId = null;

    // Step 3
    public ?int   $selectedPackageId = null;
    public string $jerseyName        = '';
    public string $jerseyNumber      = '';
    public string $memberNotes       = '';

    public function selectChild(int $childId): void
    {
        $child = Auth::user()->children()->findOrFail($childId);

        if ($child->status === 'unregistered') {
            $this->enrollmentType = 'registration';
        } elseif ($child->status === 'active') {
            $this->enrollmentType = 'program';
        } else {
            return; // pending/inactive — not enrollable
        }

        $this->selectedChildId = $childId;
        $this->step            = 2;
    }

    public function selectLocation(int $locationId): void
    {
        $this->selectedLocationId = $locationId;
        $this->selectedPackageId  = null;
    }

    public function selectSchedule(int $scheduleId): void
    {
        $schedule = Schedule::where('is_active', true)->findOrFail($scheduleId);

        $this->selectedScheduleId = $scheduleId;
        $this->selectedLocationId = $schedule->location_id;
        $this->selectedPackageId  = null;
        $this->step               = 3;
    }

    public function goToStep3Registration(): void
    {
        $this->validate([
            'selectedLocationId' => 'required|exists:locations,id',
            'selectedPackageId'  => 'required|exists:packages,id',
        ]);

        $this->step = 3;
    }

    public function submit(): void
    {
        $this->validate([
            'selectedPackageId' => 'required|exists:packages,id',
        ]);

        if ($this->enrollmentType === 'registration') {
            $this->validate([
                'jerseyName'   => 'nullable|string|max:50',
                'jerseyNumber' => 'nullable|string|max:10',
            ]);
        }

        $child   = Auth::user()->children()->findOrFail($this->selectedChildId);
        $package = Package::where('is_active', true)->findOrFail($this->selectedPackageId);

        DB::transaction(function () use ($child, $package) {
            $transaction = Transaction::create([
                'user_id'    => Auth::id(),
                'child_id'   => $child->id,
                'package_id' => $package->id,
                'amount'     => $package->price,
                'status'     => 'pending',
            ]);

            $enrollment = Enrollment::create([
                'child_id'       => $child->id,
                'type'           => $this->enrollmentType,
                'schedule_id'    => $this->selectedScheduleId,
                'package_id'     => $package->id,
                'transaction_id' => $transaction->id,
                'status'         => 'pending',
                'member_notes'   => $this->memberNotes ?: null,
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

        session()->flash('success', 'Enrollment submitted! Please upload payment proof in the Payments page.');

        $this->reset(['step', 'enrollmentType', 'selectedChildId', 'selectedLocationId',
                       'selectedScheduleId', 'selectedPackageId', 'jerseyName', 'jerseyNumber', 'memberNotes',
                       'filterDay', 'filterLocationId']);
        $this->step = 1;
    }

    public function back(): void
    {
        if ($this->step > 1) {
            $this->step--;
            $this->selectedPackageId  = null;
            $this->selectedScheduleId = null;
        }
    }

    public function resetWizard(): void
    {
        $this->reset();
        $this->step = 1;
    }

    public function render()
    {
        $user = Auth::user();

        // Step 1: children that can be enrolled
        $enrollableChildren = $user->children()
            ->whereIn('status', ['unregistered', 'active'])
            ->orderBy('name')
            ->get();

        // Step 2 — registration: active locations + packages
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        $registrationPackages = $this->selectedLocationId
            ? Package::where('location_id', $this->selectedLocationId)
                ->where('type', 'registration')
                ->where('is_active', true)
                ->get()
            : collect();

        // Step 2 — program: schedule browser
        $schedules = Schedule::with(['location', 'program', 'coach.user'])
            ->where('is_active', true)
            ->when($this->filterLocationId, fn($q) => $q->where('location_id', $this->filterLocationId))
            ->when($this->filterDay, fn($q) => $q->where('day_of_week', $this->filterDay))
            ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
            ->orderBy('start_time')
            ->get();

        // Step 3: packages at selected location
        $programPackages = $this->selectedLocationId
            ? Package::where('location_id', $this->selectedLocationId)
                ->whereIn('type', ['regular', 'drop_in'])
                ->where('is_active', true)
                ->get()
            : collect();

        return view('livewire.portal.enroll-player', compact(
            'enrollableChildren', 'locations', 'registrationPackages',
            'schedules', 'programPackages'
        ));
    }
}
