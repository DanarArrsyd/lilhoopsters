<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use Livewire\Component;
use Livewire\WithPagination;

class Players extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

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
        ]);
    }
}
