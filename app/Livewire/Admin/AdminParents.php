<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class AdminParents extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $filterStatus = '';

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function approve(int $id): void
    {
        User::findOrFail($id)->update(['registration_status' => 'approved']);
        session()->flash('success', 'Registration approved.');
    }

    public function reject(int $id): void
    {
        User::findOrFail($id)->update(['registration_status' => 'rejected']);
        session()->flash('success', 'Registration rejected.');
    }

    public function render()
    {
        $parentRoleId = Role::where('name', 'parent')->value('id');

        return view('livewire.admin.parents', [
            'parents' => User::where('role_id', $parentRoleId)
                ->when($this->filterStatus, fn($q) => $q->where('registration_status', $this->filterStatus))
                ->when($this->search, fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%"))
                ->withCount('children')
                ->orderByRaw("FIELD(registration_status,'pending','approved','rejected')")
                ->orderBy('created_at', 'desc')
                ->paginate(15),
        ]);
    }
}
