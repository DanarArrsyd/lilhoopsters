<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];

    // Month-calendar modal state
    public bool $showCalendar     = false;
    public string $calendarCursor = '';
    public ?string $selectedDate  = null;

    public function mount(): void
    {
        $this->loadStats();
        $this->calendarCursor = now()->startOfMonth()->toDateString();
        $this->selectedDate   = now()->toDateString();
    }

    public function openCalendar(): void
    {
        $this->calendarCursor = now()->startOfMonth()->toDateString();
        $this->selectedDate   = now()->toDateString();
        $this->showCalendar   = true;
    }

    public function closeCalendar(): void { $this->showCalendar = false; }

    public function prevMonth(): void
    {
        $this->calendarCursor = Carbon::parse($this->calendarCursor)->subMonthNoOverflow()->startOfMonth()->toDateString();
    }

    public function nextMonth(): void
    {
        $this->calendarCursor = Carbon::parse($this->calendarCursor)->addMonthNoOverflow()->startOfMonth()->toDateString();
    }

    public function selectDate(string $date): void { $this->selectedDate = $date; }

    public function loadStats(): void
    {
        $user     = Auth::user();
        $childIds = $user->children()->pluck('id');

        $this->stats = [
            'active_children'     => $user->children()->where('status', 'active')->count(),
            'pending_children'    => $user->children()->where('status', 'pending')->count(),
            'pending_enrollments'  => Enrollment::whereIn('child_id', $childIds)->where('status', 'pending')->count(),
            'registered_players'   => Enrollment::whereIn('child_id', $childIds)->where('status', 'approved')->where('type', 'registration')->count(),
            'active_programs'      => Enrollment::whereIn('child_id', $childIds)->where('status', 'approved')->where('type', 'program')->count(),
            'pending_payments'    => Transaction::where('user_id', $user->id)->where('status', 'pending')->count(),
            'paid_payments'       => Transaction::where('user_id', $user->id)->where('status', 'paid')->count(),
        ];
    }

    public function render()
    {
        $user     = Auth::user();
        $childIds = $user->children()->pluck('id');

        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekDays  = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i));

        // Active program enrollments with schedule
        $enrollments = Enrollment::whereIn('child_id', $childIds)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id')
            ->with(['child', 'schedule.program', 'schedule.location'])
            ->get();

        // Sessions valid on a given date: matching weekday, after enrollment
        // started, and within the exact session count (not just expires_at, which
        // can overshoot by up to 6 days).
        $sessionsOn = function (Carbon $date) use ($enrollments) {
            $day = $date->copy()->startOfDay();
            $wk  = strtolower($day->format('l'));

            return $enrollments->filter(function ($e) use ($day, $wk) {
                if ($e->schedule->day_of_week !== $wk) return false;

                // Lower bound: package hasn't started yet
                if ($e->started_at && $day->lt($e->started_at->copy()->startOfDay())) return false;

                // Upper bound: cap by total_sessions so we don't spill into the
                // next occurrence after the last paid session.
                if ($e->total_sessions && $e->started_at) {
                    // First session = first occurrence of the weekday on/after started_at
                    $firstSession = $e->started_at->copy()->startOfDay();
                    while (strtolower($firstSession->format('l')) !== $wk) {
                        $firstSession->addDay();
                    }
                    if ($day->lt($firstSession)) return false;
                    // Both dates share the weekday, so the gap is a whole number of
                    // weeks. diffInDays() is always positive here -> safe integer math.
                    $sessionNumber = intdiv($firstSession->diffInDays($day), 7) + 1;
                    if ($sessionNumber > $e->total_sessions) return false;
                } elseif ($e->expires_at && $day->gt($e->expires_at->copy()->startOfDay())) {
                    return false;
                }

                return true;
            })->values();
        };

        // Weekly widget — only this week's dates, respecting package validity.
        $schedulesByDay = collect();
        foreach ($weekDays as $day) {
            $sess = $sessionsOn($day);
            if ($sess->isNotEmpty()) {
                $schedulesByDay->put(strtolower($day->format('l')), $sess);
            }
        }

        $todaySchedules = $sessionsOn(now());

        $activePackages = Enrollment::whereIn('child_id', $childIds)
            ->where('status', 'approved')
            ->where('type', 'program')
            ->whereNotNull('schedule_id')
            ->with(['child', 'schedule.program', 'schedule.location', 'package'])
            ->get()
            ->map(function ($e) {
                $daysLeft = $e->expires_at ? now()->diffInDays($e->expires_at, false) : null;
                return [
                    'child_name'        => $e->child->name,
                    'program_name'      => $e->schedule->program->name,
                    'location_name'     => $e->schedule->location->name,
                    'expires_at'        => $e->expires_at,
                    'days_left'         => $daysLeft,
                    'remaining_sessions'=> $e->remaining_sessions,
                    'total_sessions'    => $e->total_sessions,
                    'urgency'           => $daysLeft !== null && $daysLeft <= 14 ? 'urgent' : 'normal',
                ];
            });

        // Month calendar grid — each date only counts sessions whose package
        // is still valid on that date (started_at … expires_at).
        $calendar         = null;
        $selectedSessions = collect();

        if ($this->showCalendar) {
            $monthStart = Carbon::parse($this->calendarCursor ?: now()->startOfMonth())->startOfMonth();
            $gridStart  = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd    = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $days = collect();
            for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
                $days->push([
                    'date'    => $d->toDateString(),
                    'day'     => $d->day,
                    'inMonth' => $d->month === $monthStart->month,
                    'isToday' => $d->isToday(),
                    'count'   => $sessionsOn($d)->count(),
                ]);
            }

            $calendar = [
                'label' => $monthStart->format('F Y'),
                'weeks' => $days->chunk(7)->values(),
            ];

            if ($this->selectedDate) {
                $selectedSessions = $sessionsOn(Carbon::parse($this->selectedDate));
            }
        }

        // Events running today that pause this parent's classes (package auto-extended).
        $activeEvents = \App\Models\Event::query()
            ->where('is_active', true)
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->get()
            ->filter(fn($ev) => $enrollments->contains(fn($e) =>
                (is_null($ev->location_id) || $ev->location_id === $e->schedule->location_id)
                && (is_null($ev->program_id) || $ev->program_id === $e->schedule->program_id)
            ))
            ->values();

        return view('livewire.portal.dashboard', compact(
            'weekDays', 'schedulesByDay', 'todaySchedules', 'activePackages',
            'calendar', 'selectedSessions', 'activeEvents',
        ));
    }
}
