<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Coach;
use App\Models\CoachSession;
use App\Models\Enrollment;
use App\Models\ReportCard;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ReportCards extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    // Create form
    public bool       $showForm     = false;
    public int|string $childId      = '';
    public int|string $enrollmentId = '';
    public int|string $coachId      = '';
    public string     $periodLabel  = '';
    public string     $periodStart  = '';
    public string     $periodEnd    = '';
    public string     $overallNotes = '';

    // Coach suggestion state
    public bool  $coachLocked      = false;   // true for private schedules
    public array $coachSuggestions = [];      // [{id, name, sessions}] ordered by frequency

    // Publish confirm
    public bool $showPublishModal = false;
    public ?int $publishingId     = null;

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openForm(): void
    {
        $this->reset([
            'childId', 'enrollmentId', 'coachId', 'periodLabel',
            'periodStart', 'periodEnd', 'overallNotes',
            'coachLocked', 'coachSuggestions',
        ]);
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function updatedChildId(): void
    {
        $this->enrollmentId    = '';
        $this->coachId         = '';
        $this->coachLocked     = false;
        $this->coachSuggestions = [];
    }

    public function updatedEnrollmentId(): void
    {
        $this->coachId          = '';
        $this->coachLocked      = false;
        $this->coachSuggestions = [];

        if (!$this->enrollmentId) return;

        $enrollment = Enrollment::with('schedule.coach.user')->find($this->enrollmentId);
        if (!$enrollment || !$enrollment->schedule) return;

        $schedule = $enrollment->schedule;

        // Auto-fill period dates with priority:
        // 1. enrollment.started_at / expires_at
        // 2. package.period_start / period_end
        // 3. approved_at + validity_days
        $enrollment->loadMissing('package');
        $pkg = $enrollment->package;

        $start = $enrollment->started_at
            ?? ($pkg?->period_start ? \Carbon\Carbon::parse($pkg->period_start) : null)
            ?? $enrollment->approved_at
            ?? now();

        $end = $enrollment->expires_at
            ?? ($pkg?->period_end ? \Carbon\Carbon::parse($pkg->period_end) : null)
            ?? ($pkg?->validity_days ? $start->copy()->addDays($pkg->validity_days - 1) : null);

        $this->periodStart = $start->format('Y-m-d');

        if ($end) {
            $this->periodEnd = $end->format('Y-m-d');

            $this->periodLabel = $start->format('M') === $end->format('M')
                ? $start->format('M Y')
                : $start->format('M') . '–' . $end->format('M Y');
        }

        if ($schedule->type === 'private' && $schedule->coach_id) {
            // Lock to the assigned coach
            $this->coachId     = $schedule->coach_id;
            $this->coachLocked = true;
            return;
        }

        // Regular/other: find coaches who actually taught this child in this enrollment
        $suggestions = DB::table('coach_sessions as cs')
            ->join('attendances as a', function ($join) {
                $join->on('a.schedule_id', '=', 'cs.schedule_id')
                     ->whereRaw('DATE(a.attended_at) = cs.session_date');
            })
            ->join('coaches as c', 'c.id', '=', 'cs.coach_id')
            ->join('users as u', 'u.id', '=', 'c.user_id')
            ->where('a.child_id', $this->childId)
            ->where('a.enrollment_id', $this->enrollmentId)
            ->where('a.status', 'present')
            ->select('cs.coach_id', 'u.name', DB::raw('COUNT(*) as sessions'))
            ->groupBy('cs.coach_id', 'u.name')
            ->orderByDesc('sessions')
            ->get();

        $this->coachSuggestions = $suggestions->map(fn($r) => [
            'id'       => $r->coach_id,
            'name'     => $r->name,
            'sessions' => $r->sessions,
        ])->toArray();

        // Auto-select top coach if there's data
        if (!empty($this->coachSuggestions)) {
            $this->coachId = $this->coachSuggestions[0]['id'];
        }
    }

    public function create(): void
    {
        $this->validate([
            'childId'      => 'required|exists:children,id',
            'enrollmentId' => 'required|exists:enrollments,id',
            'coachId'      => 'required|exists:coaches,id',
            'periodLabel'  => 'required|string|max:100',
            'periodStart'  => 'required|date',
            'periodEnd'    => 'required|date|after_or_equal:periodStart',
        ]);

        ReportCard::create([
            'child_id'      => $this->childId,
            'enrollment_id' => $this->enrollmentId,
            'coach_id'      => $this->coachId,
            'period_label'  => $this->periodLabel,
            'period_start'  => $this->periodStart,
            'period_end'    => $this->periodEnd,
            'overall_notes' => $this->overallNotes ?: null,
            'status'        => 'draft',
        ]);

        $this->showForm = false;
        session()->flash('success', 'Report card created.');
    }

    public function confirmPublish(int $id): void
    {
        $this->publishingId     = $id;
        $this->showPublishModal = true;
    }

    public function publish(): void
    {
        $card = ReportCard::findOrFail($this->publishingId);
        $card = ReportCard::with(['child.user'])->findOrFail($this->publishingId);
        $card->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

        $parentUser = $card->child?->user;
        if ($parentUser) {
            NotificationService::send(
                $parentUser->id,
                'report_card_published',
                'Report Card Published 📋',
                "The report card for {$card->child->name} ({$card->period_label}) is now available. Tap to view.",
            );
        }

        $this->showPublishModal = false;
        $this->publishingId     = null;
        session()->flash('success', 'Report card published.');
    }

    public function cancelPublish(): void
    {
        $this->showPublishModal = false;
        $this->publishingId     = null;
    }

    public function render()
    {
        $cards = ReportCard::query()
            ->when($this->search, fn($q) => $q->whereHas('child', fn($cq) =>
                $cq->where('name', 'like', '%' . $this->search . '%')
            ))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->with(['child', 'coach.user', 'enrollment.schedule'])
            ->latest()
            ->paginate(15);

        $children = Child::orderBy('name')->get();
        $coaches  = Coach::with('user')->get();

        $enrollments = $this->childId
            ? Enrollment::where('child_id', $this->childId)
                ->where('status', 'approved')
                ->where('type', 'program')
                ->whereNotNull('schedule_id')
                ->with('schedule.program')
                ->get()
            : collect();

        return view('livewire.admin.report-cards', compact(
            'cards', 'children', 'coaches', 'enrollments'
        ));
    }
}
