<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Enrollments extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';
    public string $filterType   = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }
    public function updatingFilterType(): void   { $this->resetPage(); }

    public function approve(int $id): void
    {
        DB::transaction(function () use ($id) {
            $enrollment = Enrollment::with(['child', 'package', 'schedule'])->findOrFail($id);

            $data = [
                'status'      => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ];

            if ($enrollment->type === 'registration') {
                $enrollment->child->update([
                    'status'        => 'active',
                    'registered_at' => now(),
                ]);
            } elseif ($enrollment->type === 'program') {
                $startedAt = $this->firstScheduleDate($enrollment);
                $data['started_at'] = $startedAt;
                $data['expires_at'] = $enrollment->package->period_end
                    ?? ($enrollment->package->validity_days
                        ? $startedAt->copy()->addDays($enrollment->package->validity_days - 1)
                        : null);

                if ($enrollment->package->session_count) {
                    $data['total_sessions']     = $enrollment->package->session_count;
                    $data['remaining_sessions'] = $enrollment->package->session_count;
                }
            }

            $enrollment->update($data);
        });

        $enrollment = Enrollment::with(['child.user'])->findOrFail($id);

        AuditLog::record(
            'enrollment.approved',
            $enrollment,
            'Approved enrollment for ' . ($enrollment->child?->name ?? 'unknown child'),
        );

        $parentUser = $enrollment->child?->user;
        if ($parentUser) {
            NotificationService::send(
                $parentUser->id,
                'enrollment_approved',
                'Enrollment Approved',
                "Your enrollment for {$enrollment->child->name} has been approved. See you on the court! 🏀",
            );
        }

        session()->flash('success', 'Enrollment approved.');
    }

    public function reject(int $id): void
    {
        $enrollment = Enrollment::with(['child.user'])->findOrFail($id);
        $enrollment->update(['status' => 'rejected']);

        AuditLog::record(
            'enrollment.rejected',
            $enrollment,
            'Rejected enrollment for ' . ($enrollment->child?->name ?? 'unknown child'),
        );

        $parentUser = $enrollment->child?->user;
        if ($parentUser) {
            NotificationService::send(
                $parentUser->id,
                'enrollment_rejected',
                'Enrollment Not Approved',
                "Unfortunately, the enrollment for {$enrollment->child->name} was not approved. Please contact us for more information.",
            );
        }

        session()->flash('success', 'Enrollment rejected.');
    }

    private function firstScheduleDate(Enrollment $enrollment): Carbon
    {
        $dayMap = [
            'monday'    => Carbon::MONDAY,
            'tuesday'   => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday'  => Carbon::THURSDAY,
            'friday'    => Carbon::FRIDAY,
            'saturday'  => Carbon::SATURDAY,
            'sunday'    => Carbon::SUNDAY,
        ];

        $targetDay = $dayMap[$enrollment->schedule->day_of_week ?? 'monday'] ?? Carbon::MONDAY;
        $today     = today();

        // If today is already the schedule day, start today; otherwise find the next occurrence
        return $today->dayOfWeek === $targetDay
            ? $today
            : $today->copy()->next($targetDay);
    }

    public function render()
    {
        return view('livewire.admin.enrollments', [
            'enrollments' => Enrollment::with(['child', 'package.location', 'schedule'])
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->filterType,   fn($q) => $q->where('type', $this->filterType))
                ->when($this->search, fn($q) => $q
                    ->whereHas('child', fn($c) => $c->where('name', 'like', "%{$this->search}%")))
                ->orderByRaw("FIELD(status,'pending','approved','rejected','expired')")
                ->orderBy('created_at', 'desc')
                ->paginate(15),
        ]);
    }
}
