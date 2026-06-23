<?php

namespace App\Livewire\Portal;

use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PrivateSessions extends Component
{
    public int $step = 1; // 1=player, 2=location, 3=coach, 4=package+slot, 5=confirm

    public ?int $selectedChildId     = null;
    public ?int $selectedLocationId  = null;
    public ?int $selectedCoachId     = null;
    public ?int $selectedScheduleId  = null;
    public ?int $selectedPackageId   = null;
    public string $previewTrxCode    = '';

    public function selectChild(int $childId): void
    {
        $this->selectedChildId    = $childId;
        $this->selectedLocationId = null;
        $this->selectedCoachId    = null;
        $this->selectedScheduleId = null;
        $this->selectedPackageId  = null;
        $this->step = 2;
    }

    public function selectLocation(int $locationId): void
    {
        $this->selectedLocationId = $locationId;
        $this->selectedCoachId    = null;
        $this->selectedScheduleId = null;
        $this->selectedPackageId  = null;
        $this->step = 3;
    }

    public function selectCoach(int $coachId): void
    {
        $this->selectedCoachId    = $coachId;
        $this->selectedScheduleId = null;
        $this->selectedPackageId  = null;
        $this->step = 4;
    }

    public function selectSchedule(int $scheduleId): void
    {
        // Step 4: the time slot is picked alongside the package, so this only
        // records the choice without advancing the wizard.
        $this->selectedScheduleId = $scheduleId;
    }

    public function back(): void
    {
        if ($this->step <= 1) return;
        $this->step--;

        if ($this->step < 4) {
            $this->selectedScheduleId = null;
            $this->selectedPackageId  = null;
        }
        if ($this->step < 3) {
            $this->selectedCoachId = null;
        }
        if ($this->step < 2) {
            $this->selectedLocationId = null;
        }
    }

    public function confirmDetails(): void
    {
        $this->validate([
            'selectedChildId'    => 'required|integer|exists:children,id',
            'selectedScheduleId' => 'required|integer|exists:schedules,id',
            'selectedPackageId'  => 'required|integer|exists:packages,id',
        ]);

        $this->previewTrxCode = 'PRIV-' . strtoupper(substr(uniqid(), -6));
        $this->step = 5;
    }

    public function submit(): void
    {
        $this->validate([
            'selectedScheduleId' => 'required|integer|exists:schedules,id',
            'selectedChildId'    => 'required|integer|exists:children,id',
            'selectedPackageId'  => 'required|integer|exists:packages,id',
        ]);

        $user     = Auth::user();
        $child    = $user->children()->findOrFail($this->selectedChildId);
        $package  = Package::where('is_active', true)->findOrFail($this->selectedPackageId);
        $schedule = Schedule::findOrFail($this->selectedScheduleId);

        // H-1 enforcement
        $nextOcc = $this->nextOccurrence($schedule->day_of_week);
        if ($nextOcc->lt(now()->addDay()->startOfDay())) {
            session()->flash('error', 'Booking must be made at least 1 day in advance (H-1).');
            $this->step = 4;
            return;
        }

        // Capacity check
        $enrolled = Enrollment::where('schedule_id', $schedule->id)
            ->where('status', 'approved')
            ->count();
        if ($enrolled >= $schedule->max_capacity) {
            session()->flash('error', 'This time slot is fully booked.');
            $this->step = 4;
            return;
        }

        DB::transaction(function () use ($user, $child, $package, $schedule) {
            $transaction = Transaction::create([
                'user_id'          => $user->id,
                'child_id'         => $child->id,
                'package_id'       => $package->id,
                'amount'           => $package->price,
                'status'           => 'pending',
                'transaction_code' => $this->previewTrxCode ?: null,
            ]);

            $enrollment = Enrollment::create([
                'child_id'       => $child->id,
                'schedule_id'    => $schedule->id,
                'type'           => 'program',
                'status'         => 'pending',
                'package_id'     => $package->id,
                'transaction_id' => $transaction->id,
                'total_sessions' => $package->session_count,
            ]);

            $transaction->update(['enrollment_id' => $enrollment->id]);
        });

        NotificationService::toAdmins(
            'new_enrollment',
            'New Private Session Booking',
            Auth::user()->name . " has booked a private session for {$child->name}. Please review.",
        );

        session()->flash('success', 'Private session booked! Please upload payment proof from the Payments page.');
        $this->redirect(route('parent.payments'), navigate: true);
    }

    public function nextOccurrence(string $dayOfWeek): Carbon
    {
        $isoDays = ['monday'=>1,'tuesday'=>2,'wednesday'=>3,'thursday'=>4,'friday'=>5,'saturday'=>6,'sunday'=>7];
        $target  = $isoDays[$dayOfWeek];
        $today   = now();
        $diff    = ($target - $today->dayOfWeekIso + 7) % 7;
        if ($diff === 0) $diff = 7;
        return $today->copy()->addDays($diff)->startOfDay();
    }

    public function render()
    {
        $user = Auth::user();

        // The parent's active players (chosen first).
        $children = $user->children()->where('status', 'active')->get();

        // Only locations that have active private schedules.
        $locations = Location::where('is_active', true)
            ->whereHas('schedules', fn($q) => $q->where('type', 'private')->where('is_active', true))
            ->orderBy('name')
            ->get();

        $coaches          = collect();
        $scheduleSlots    = collect();
        $packages         = collect();
        $selectedLocation = null;
        $selectedCoach    = null;

        if ($this->selectedLocationId) {
            $selectedLocation = $locations->firstWhere('id', $this->selectedLocationId);

            // Coaches running private sessions at this location.
            $coaches = Coach::where('is_active', true)
                ->whereHas('schedules', fn($q) => $q
                    ->where('type', 'private')
                    ->where('is_active', true)
                    ->where('location_id', $this->selectedLocationId))
                ->with('user')
                ->get()
                ->sortBy(fn($c) => $c->user?->name)
                ->values();
        }

        if ($this->selectedLocationId && $this->selectedCoachId) {
            $selectedCoach = $coaches->firstWhere('id', $this->selectedCoachId);

            $scheduleSlots = Schedule::where('type', 'private')
                ->where('is_active', true)
                ->where('location_id', $this->selectedLocationId)
                ->where('coach_id', $this->selectedCoachId)
                ->with(['coach.user', 'program'])
                ->orderByRaw("FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')")
                ->orderBy('start_time')
                ->get()
                ->map(function ($s) {
                    $enrolled  = Enrollment::where('schedule_id', $s->id)->where('status', 'approved')->count();
                    $remaining = max(0, $s->max_capacity - $enrolled);
                    $nextOcc   = $this->nextOccurrence($s->day_of_week);
                    $available = $nextOcc->gte(now()->addDay()->startOfDay()) && $remaining > 0;
                    return [
                        'schedule'  => $s,
                        'enrolled'  => $enrolled,
                        'remaining' => $remaining,
                        'nextOcc'   => $nextOcc,
                        'available' => $available,
                    ];
                });

            $packages = Package::where('type', 'private')
                ->where('location_id', $this->selectedLocationId)
                ->where('is_active', true)
                ->orderBy('price')
                ->get();
        }

        $selectedChild    = $this->selectedChildId
            ? $children->firstWhere('id', $this->selectedChildId)
            : null;
        $selectedSchedule = $this->selectedScheduleId
            ? Schedule::with(['coach.user', 'program', 'location'])->find($this->selectedScheduleId)
            : null;
        $selectedPackage  = $this->selectedPackageId
            ? $packages->firstWhere('id', $this->selectedPackageId)
            : null;

        return view('livewire.portal.private-sessions', compact(
            'children', 'locations', 'coaches', 'scheduleSlots', 'packages',
            'selectedChild', 'selectedLocation', 'selectedCoach',
            'selectedSchedule', 'selectedPackage',
        ))->layout('components.app', ['title' => 'Private Sessions']);
    }
}
