<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\ReportCard;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ReportCards extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    // Create form
    public bool $showForm     = false;
    public int|string $childId      = '';
    public int|string $enrollmentId = '';
    public int|string $coachId      = '';
    public string $periodLabel  = '';
    public string $periodStart  = '';
    public string $periodEnd    = '';
    public string $overallNotes = '';

    // Publish confirm
    public bool $showPublishModal = false;
    public ?int $publishingId     = null;

    public function updatingSearch(): void  { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openForm(): void
    {
        $this->reset(['childId', 'enrollmentId', 'coachId', 'periodLabel', 'periodStart', 'periodEnd', 'overallNotes']);
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function updatedChildId(): void
    {
        $this->enrollmentId = '';
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
            'overall_notes' => $this->overallNotes,
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
        $card->update([
            'status'       => 'published',
            'published_at' => now(),
        ]);

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
            ->with(['child', 'coach.user', 'enrollment'])
            ->latest()
            ->paginate(15);

        $children = Child::orderBy('name')->get();
        $coaches  = Coach::with('user')->get();

        $enrollments = $this->childId
            ? Enrollment::where('child_id', $this->childId)->where('status', 'approved')->with('schedule.program')->get()
            : collect();

        return view('livewire.admin.report-cards', compact('cards', 'children', 'coaches', 'enrollments'));
    }
}
