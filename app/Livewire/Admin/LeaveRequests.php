<?php

namespace App\Livewire\Admin;

use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class LeaveRequests extends Component
{
    use WithPagination;

    public string $filterStatus = 'pending';
    public string $search       = '';

    // Review modal
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

        LeaveRequest::findOrFail($this->reviewId)->update([
            'status'      => $status,
            'admin_notes' => $this->adminNotes ?: null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->showReview = false;
        $this->reviewId   = null;
        session()->flash('success', 'Leave request ' . $status . '.');
    }

    public function closeReview(): void
    {
        $this->showReview = false;
        $this->reviewId   = null;
    }

    public function render()
    {
        $query = LeaveRequest::with(['child', 'enrollment', 'schedule.program', 'schedule.location'])
            ->when($this->search, function ($q) {
                $q->whereHas('child', fn($c) => $c->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->latest();

        $counts = [
            'pending'       => LeaveRequest::where('status', 'pending')->count(),
            'approved'      => LeaveRequest::whereIn('status', ['approved', 'auto_approved'])->count(),
            'rejected'      => LeaveRequest::where('status', 'rejected')->count(),
            'auto_approved' => LeaveRequest::where('status', 'auto_approved')->count(),
        ];

        return view('livewire.admin.leave-requests', [
            'requests' => $query->paginate(20),
            'counts'   => $counts,
        ]);
    }
}
