<?php

namespace App\Livewire\Admin;

use App\Models\MakeUpClass;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class MakeUpClasses extends Component
{
    use WithPagination;

    public string $filterStatus = 'pending';
    public string $search       = '';

    public bool $showReview    = false;
    public ?int $reviewId      = null;
    public string $reviewAction = '';
    public string $adminNotes   = '';

    public function updatedFilterStatus(): void { $this->resetPage(); }
    public function updatedSearch(): void       { $this->resetPage(); }

    public function openReview(int $id, string $action): void
    {
        $this->reviewId     = $id;
        $this->reviewAction = $action;
        $this->adminNotes   = '';
        $this->showReview   = true;
    }

    public function saveReview(): void
    {
        $this->validate([
            'adminNotes' => 'nullable|string|max:500',
        ]);

        $status = $this->reviewAction === 'approve' ? 'approved' : 'rejected';

        MakeUpClass::findOrFail($this->reviewId)->update([
            'status'      => $status,
            'admin_notes' => $this->adminNotes ?: null,
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->showReview = false;
        $this->reviewId   = null;
        session()->flash('success', 'Make-up class request ' . $status . '.');
    }

    public function closeReview(): void
    {
        $this->showReview = false;
        $this->reviewId   = null;
    }

    public function render()
    {
        $query = MakeUpClass::with(['child', 'targetSchedule.program', 'targetSchedule.location', 'leaveRequest'])
            ->when($this->search, function ($q) {
                $q->whereHas('child', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest();

        $counts = [
            'pending'   => MakeUpClass::where('status', 'pending')->count(),
            'approved'  => MakeUpClass::where('status', 'approved')->count(),
            'rejected'  => MakeUpClass::where('status', 'rejected')->count(),
            'completed' => MakeUpClass::where('status', 'completed')->count(),
        ];

        return view('livewire.admin.makeup-classes', [
            'makeUpClasses' => $query->paginate(20),
            'counts'        => $counts,
        ]);
    }
}
