<?php

namespace App\Livewire\Coach;

use App\Models\Enrollment;
use App\Models\Schedule;
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
        $coach = Auth::user()->coach;

        if (!$coach) {
            $this->stats = ['total_schedules' => 0, 'today_schedules' => 0, 'active_students' => 0];
            return;
        }

        $today = strtolower(now()->format('l'));

        // Coach can attend regular (any) + their private schedules
        $allSchedules = Schedule::where('is_active', true)
            ->where(function ($q) use ($coach) {
                $q->where('type', 'regular')
                  ->orWhere(fn($q2) => $q2->where('type', 'private')->where('coach_id', $coach->id));
            })
            ->with('enrollments')
            ->get();

        $this->stats = [
            'total_schedules' => $allSchedules->count(),
            'today_schedules' => $allSchedules->where('day_of_week', $today)->count(),
            'active_students' => $allSchedules->sum(
                fn($s) => $s->enrollments->where('status', 'approved')->where('type', 'program')->count()
            ),
        ];
    }

    public function render()
    {
        $coach = Auth::user()->coach;

        // Mon-Sun of current week
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekDays  = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i));

        $today = strtolower(now()->format('l'));

        // All schedules accessible to this coach, grouped by day
        $schedulesByDay = $coach
            ? Schedule::where('is_active', true)
                ->where(function ($q) use ($coach) {
                    $q->where('type', 'regular')
                      ->orWhere(fn($q2) => $q2->where('type', 'private')->where('coach_id', $coach->id));
                })
                ->with(['program', 'location'])
                ->orderBy('start_time')
                ->get()
                ->groupBy('day_of_week')
            : collect();

        $todaySchedules = $schedulesByDay->get($today, collect());

        // Month calendar grid (sessions recur weekly by day_of_week)
        $calendar         = null;
        $selectedSessions = collect();

        if ($this->showCalendar) {
            $monthStart = Carbon::parse($this->calendarCursor ?: now()->startOfMonth())->startOfMonth();
            $gridStart  = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd    = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $days = collect();
            for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
                $wk = strtolower($d->format('l'));
                $days->push([
                    'date'    => $d->toDateString(),
                    'day'     => $d->day,
                    'inMonth' => $d->month === $monthStart->month,
                    'isToday' => $d->isToday(),
                    'count'   => $schedulesByDay->has($wk) ? $schedulesByDay->get($wk)->count() : 0,
                ]);
            }

            $calendar = [
                'label' => $monthStart->format('F Y'),
                'weeks' => $days->chunk(7)->values(),
            ];

            if ($this->selectedDate) {
                $selWk = strtolower(Carbon::parse($this->selectedDate)->format('l'));
                $selectedSessions = $schedulesByDay->get($selWk, collect());
            }
        }

        return view('livewire.coach.dashboard', compact(
            'weekDays', 'schedulesByDay', 'todaySchedules',
            'calendar', 'selectedSessions',
        ));
    }
}
