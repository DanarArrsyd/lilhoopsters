<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;

class Players extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    public bool $showLtv     = false;
    public ?int $ltvChildId  = null;

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function openLtv(int $childId): void
    {
        $this->ltvChildId = $childId;
        $this->showLtv    = true;
    }

    public function closeLtv(): void
    {
        $this->showLtv    = false;
        $this->ltvChildId = null;
    }

    public function render()
    {
        return view('livewire.admin.players', [
            'players' => Child::with('parent')
                ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
                ->when($this->search, fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhereHas('parent', fn($u) => $u->where('name', 'like', "%{$this->search}%")))
                ->orderByRaw("FIELD(status,'pending','active','unregistered','inactive')")
                ->orderBy('name')
                ->paginate(15),
            'ltvChild'        => $this->ltvChildId ? Child::with('parent')->find($this->ltvChildId) : null,
            'ltvTotal'        => $this->ltvChildId ? Transaction::where('child_id', $this->ltvChildId)->where('status', 'paid')->sum('amount') : 0,
            'ltvTransactions' => $this->ltvChildId
                ? Transaction::where('child_id', $this->ltvChildId)->with('package')->latest('created_at')->get()
                : collect(),
        ]);
    }
}
