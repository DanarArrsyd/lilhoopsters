<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AttendanceHistory extends Component
{
    public string $filterChildId    = '';
    public ?int   $filterEnrollment = null;

    public function mount(): void
    {
        $user  = Auth::user();
        $first = $user->children()->whereIn('status', ['active', 'inactive'])->orderBy('name')->first();
        if ($first) {
            $this->filterChildId = (string) $first->id;
        }
    }

    public function selectChild(int $childId): void
    {
        $this->filterChildId    = (string) $childId;
        $this->filterEnrollment = null;
    }

    public function selectEnrollment(?int $enrollmentId): void
    {
        $this->filterEnrollment = $enrollmentId;
    }

    public function render()
    {
        $user     = Auth::user();
        $children = $user->children()->whereIn('status', ['active', 'inactive'])->orderBy('name')->get();

        // Approved program enrollments for the selected child
        $enrollmentsQuery = Enrollment::where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id')
            ->with(['schedule.program', 'schedule.location', 'attendances'])
            ->when($this->filterChildId, fn($q) => $q->where('child_id', $this->filterChildId));

        $enrollments = $enrollmentsQuery->get();

        // Default to first enrollment if none selected
        $activeEnrollment = $this->filterEnrollment
            ? $enrollments->firstWhere('id', $this->filterEnrollment)
            : $enrollments->first();

        $sessions = collect();

        if ($activeEnrollment) {
            $sessions = $this->generateSessions($activeEnrollment);
        }

        return view('livewire.portal.attendance', compact('children', 'enrollments', 'activeEnrollment', 'sessions'));
    }

    private function generateSessions(Enrollment $enrollment): \Illuminate\Support\Collection
    {
        $schedule  = $enrollment->schedule;
        $startDate = $enrollment->started_at
            ? Carbon::parse($enrollment->started_at)
            : Carbon::parse($enrollment->created_at)->startOfWeek();
        $endDate = $enrollment->expires_at
            ? Carbon::parse($enrollment->expires_at)
            : now();

        // Map day_of_week string to Carbon constant
        $dayMap = [
            'monday'    => Carbon::MONDAY,
            'tuesday'   => Carbon::TUESDAY,
            'wednesday' => Carbon::WEDNESDAY,
            'thursday'  => Carbon::THURSDAY,
            'friday'    => Carbon::FRIDAY,
            'saturday'  => Carbon::SATURDAY,
            'sunday'    => Carbon::SUNDAY,
        ];
        $targetDay = $dayMap[$schedule->day_of_week] ?? Carbon::MONDAY;

        // Find first occurrence of the target day on or after startDate
        $current = $startDate->copy();
        if ($current->dayOfWeek !== $targetDay) {
            $current->next($targetDay);
        }

        // Build an attendance lookup keyed by date string
        $attendanceLookup = $enrollment->attendances
            ->keyBy(fn($a) => Carbon::parse($a->attended_at)->format('Y-m-d'));

        // Active events pause this schedule — skip those dates entirely.
        $events = \App\Services\EventService::eventsForSchedule(
            $schedule->location_id, $schedule->program_id
        );

        $sessions = collect();
        $sessionNumber = 1;

        $maxSessions = $enrollment->total_sessions ?: null;

        while ($current->lte($endDate)) {
            if ($maxSessions && $sessionNumber > $maxSessions) break;

            // Skip weeks that fall inside an event period (classes paused).
            $paused = $events->contains(
                fn($e) => $current->betweenIncluded($e->start_date, $e->end_date)
            );
            if ($paused) {
                $current->addWeek();
                continue;
            }

            $dateKey    = $current->format('Y-m-d');
            $attendance = $attendanceLookup->get($dateKey);
            $isPast     = $current->isPast() && !$current->isToday();
            $isToday    = $current->isToday();

            if ($attendance) {
                $statusMap = [
                    'present' => ['label' => __('messages.attendance.badge.attend'),   'class' => 'bg-green-50 text-green-700'],
                    'sick'    => ['label' => __('messages.attendance.badge.sick'),     'class' => 'bg-amber-50 text-amber-700'],
                    'permit'  => ['label' => __('messages.attendance.badge.permit'),   'class' => 'bg-blue-50 text-blue-700'],
                    'no_show' => ['label' => __('messages.attendance.badge.no_show'),  'class' => 'bg-red-50 text-red-600'],
                    'make_up' => ['label' => __('messages.attendance.badge.make_up'),  'class' => 'bg-violet-50 text-violet-700'],
                ];
                $badge = $statusMap[$attendance->status] ?? ['label' => ucfirst($attendance->status), 'class' => 'bg-off text-muted'];
            } elseif ($isToday) {
                $badge = ['label' => __('messages.attendance.badge.today'),     'class' => 'bg-navy text-white'];
            } elseif ($isPast) {
                $badge = ['label' => __('messages.attendance.badge.no_record'), 'class' => 'bg-line text-faint'];
            } else {
                $badge = ['label' => __('messages.attendance.badge.upcoming'),  'class' => 'bg-off text-muted'];
            }

            $sessions->push([
                'number'     => $sessionNumber,
                'date'       => $current->copy(),
                'badge'      => $badge,
                'is_past'    => $isPast,
                'is_today'   => $isToday,
                'attendance' => $attendance,
            ]);

            $sessionNumber++;
            $current->addWeek();
        }

        return $sessions->values();
    }
}
