<?php

namespace App\Livewire\Admin;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Payments extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    public ?int $rejectingId    = null;
    public string $adminNote    = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function verify(int $id): void
    {
        Transaction::findOrFail($id)->update([
            'status'      => 'paid',
            'verified_by' => Auth::id(),
            'paid_at'     => now(),
        ]);
        session()->flash('success', 'Payment verified.');
    }

    public function reject(int $id): void
    {
        $this->rejectingId = $id;
        $this->adminNote   = '';
    }

    public function confirmReject(): void
    {
        Transaction::findOrFail($this->rejectingId)->update([
            'status'      => 'rejected',
            'admin_notes' => $this->adminNote ?: null,
        ]);

        $this->rejectingId = null;
        $this->adminNote   = '';
        session()->flash('success', 'Payment rejected.');
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->adminNote   = '';
    }

    public function render()
    {
        return view('livewire.admin.payments', [
            'transactions' => Transaction::with(['user', 'child', 'package.location'])
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->search, fn($q) => $q
                    ->where('transaction_code', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$this->search}%")))
                ->orderByRaw("FIELD(status,'pending','paid','rejected','expired')")
                ->orderBy('created_at', 'desc')
                ->paginate(15),
        ]);
    }
}
