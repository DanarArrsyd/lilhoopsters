<?php

namespace App\Livewire\Portal;

use App\Models\Child;
use App\Models\Event;
use App\Support\ChildSchedulePlanner;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Home extends Component
{
    public ?int $activeChildId = null;

    public function mount(): void
    {
        $storedId = session('portal_active_child_id');
        $children = Auth::user()->children()->orderBy('name')->get();

        $this->activeChildId = ($storedId && $children->contains('id', $storedId))
            ? $storedId
            : $children->first()?->id;
    }

    public function switchChild(int $childId): void
    {
        Auth::user()->children()->findOrFail($childId);

        $this->activeChildId = $childId;
        session(['portal_active_child_id' => $childId]);
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
