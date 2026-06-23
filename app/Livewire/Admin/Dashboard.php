<?php

namespace App\Livewire\Admin;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];

    // Month-calendar modal state
    public bool $showCalendar      = false;
    public string $calendarCursor  = '';   // first day of the month being viewed (Y-m-d)
    public ?string $selectedDate   = null; // day whose sessions are shown (Y-m-d)

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

    public function closeCalendar(): void
    {
        $this->showCalendar = false;
    }

    public function prevMonth(): void
    {
        $this->calendarCursor = Carbon::parse($this->calendarCursor)->subMonthNoOverflow()->startOfMonth()->toDateString();
    }

    public function nextMonth(): void
    {
        $this->calendarCursor = Carbon::parse($this->calendarCursor)->addMonthNoOverflow()->startOfMonth()->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function loadStats(): void
    {
        $parentRoleId = Role::where('name', 'parent')->value('id');

        $this->stats = [
            'pending_registrations' => User::where('role_id', $parentRoleId)
                ->where('registration_status', 'pending')
                ->count(),
            'active_players'      => Child::where('status', 'active')->count(),
            'pending_enrollments' => Enrollment::where('status', 'pending')->count(),
            'pending_payments'    => Transaction::where('status', 'pending')->count(),
            'active_locations'    => Location::where('is_active', true)->count(),
            'active_coaches'      => Coach::where('is_active', true)->count(),
        ];
    }

    public function render()
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY);
        $weekDays  = collect(range(0, 6))->map(fn($i) => $weekStart->copy()->addDays($i));

        $today = strtolower(now()->format('l'));

        $schedulesByDay = Schedule::where('is_active', true)
            ->with(['program', 'location', 'coach.user'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $todaySchedules = $schedulesByDay->get($today, collect());

        // Per-schedule attendance counts for today
        $attendanceCounts = Attendance::whereDate('attended_at', today())
            ->select('schedule_id', DB::raw('COUNT(*) as recorded'), DB::raw('SUM(status = "present") as present'))
            ->groupBy('schedule_id')
            ->get()
            ->keyBy('schedule_id');

        $todayActivity = $todaySchedules->map(function ($s) use ($attendanceCounts) {
            $enrolled = Enrollment::where('schedule_id', $s->id)->where('status', 'approved')->count();
            $att      = $attendanceCounts->get($s->id);
            return [
                'schedule' => $s,
                'enrolled' => $enrolled,
                'recorded' => $att?->recorded ?? 0,
                'present'  => $att?->present ?? 0,
            ];
        });

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

        return view('livewire.admin.dashboard', compact(
            'weekDays', 'schedulesByDay', 'todaySchedules', 'todayActivity',
            'calendar', 'selectedSessions',
        ));
    }
}
