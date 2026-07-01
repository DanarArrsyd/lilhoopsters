<?php

namespace App\Livewire\Portal;

use App\Models\Child;
use App\Models\Event;
use App\Support\ChildSchedulePlanner;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Home extends Component
{
    public ?int $activeChildId = null;

    public bool $showCalendar    = false;
    public string $calendarCursor = '';
    public ?string $selectedDate  = null;

    public function mount(): void
    {
        $storedId = session('portal_active_child_id');
        $children = Auth::user()->children()->orderBy('name')->get();

        $this->activeChildId = ($storedId && $children->contains('id', $storedId))
            ? $storedId
            : $children->first()?->id;

        $this->calendarCursor = now()->startOfMonth()->toDateString();
        $this->selectedDate   = now()->toDateString();
    }

    public function switchChild(int $childId): void
    {
        Auth::user()->children()->findOrFail($childId);

        $this->activeChildId = $childId;
        session(['portal_active_child_id' => $childId]);
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

    public function getChildrenProperty()
    {
        return Auth::user()->children()->orderBy('name')->get();
    }

    public function getActiveChildProperty(): ?Child
    {
        if (! $this->activeChildId) {
            return null;
        }

        return Auth::user()->children()
            ->with(['enrollments.package', 'enrollments.schedule.location'])
            ->find($this->activeChildId);
    }

    public function render()
    {
        $child = $this->activeChild;
        $sectionFailed = false;

        [$nextSession, $weekSessions] = $this->safely(function () use ($child) {
            return $child
                ? [ChildSchedulePlanner::nextSession($child), ChildSchedulePlanner::weekSessions($child)]
                : [null, collect()];
        }, [null, collect()], $sectionFailed);

        [$calendar, $selectedSessions] = $this->safely(function () use ($child) {
            if (! $child || ! $this->showCalendar) {
                return [null, collect()];
            }

            $enrollments = ChildSchedulePlanner::approvedEnrollments($child);
            $monthStart  = Carbon::parse($this->calendarCursor ?: now()->startOfMonth())->startOfMonth();
            $gridStart   = $monthStart->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd     = $monthStart->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $days = collect();
            for ($d = $gridStart->copy(); $d->lte($gridEnd); $d->addDay()) {
                $days->push([
                    'date'    => $d->toDateString(),
                    'day'     => $d->day,
                    'inMonth' => $d->month === $monthStart->month,
                    'isToday' => $d->isToday(),
                    'count'   => ChildSchedulePlanner::sessionsOn($enrollments, $d->copy())->count(),
                ]);
            }

            $calendar = [
                'label' => $monthStart->translatedFormat('F Y'),
                'weeks' => $days->chunk(7)->values(),
            ];

            $selectedSessions = $this->selectedDate
                ? ChildSchedulePlanner::sessionsOn($enrollments, Carbon::parse($this->selectedDate))
                : collect();

            return [$calendar, $selectedSessions];
        }, [null, collect()], $sectionFailed);

        [$transactions, $pendingAmount] = $this->safely(function () use ($child) {
            return $child
                ? [
                    Auth::user()->transactions()->where('child_id', $child->id)->with('package')->latest()->take(5)->get(),
                    Auth::user()->transactions()->where('child_id', $child->id)->where('status', 'pending')->sum('amount'),
                ]
                : [collect(), 0];
        }, [collect(), 0], $sectionFailed);

        $attendanceCounts = $this->safely(function () use ($child) {
            return $child
                ? $child->attendances()
                    ->whereMonth('attended_at', now()->month)
                    ->whereYear('attended_at', now()->year)
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status')
                : collect();
        }, collect(), $sectionFailed);

        $activeEvent = $this->safely(function () use ($child) {
            if (! $child) {
                return null;
            }
            $locationIds = $child->enrollments->pluck('schedule.location_id')->filter()->values();

            return Event::where('is_active', true)
                ->where('is_registerable', true)
                ->whereDate('end_date', '>=', today())
                ->where(fn ($q) => $q->whereNull('location_id')->orWhereIn('location_id', $locationIds))
                ->first();
        }, null, $sectionFailed);

        return view('livewire.portal.home', [
            'children'         => $this->children,
            'child'            => $child,
            'nextSession'      => $nextSession,
            'weekSessions'     => $weekSessions,
            'calendar'         => $calendar,
            'selectedSessions' => $selectedSessions,
            'transactions'     => $transactions,
            'pendingAmount'    => $pendingAmount,
            'attendanceCounts' => $attendanceCounts,
            'activeEvent'      => $activeEvent,
            'sectionFailed'    => $sectionFailed,
        ])->layout('components.app', ['title' => 'Home']);
    }

    /**
     * Run a data-loading closure; on failure, log it, flag $sectionFailed by
     * reference, and fall back to a safe default so one bad query can't take
     * down the whole page.
     */
    private function safely(callable $fn, mixed $default, bool &$sectionFailed): mixed
    {
        try {
            return $fn();
        } catch (\Throwable $e) {
            report($e);
            $sectionFailed = true;

            return $default;
        }
    }
}
