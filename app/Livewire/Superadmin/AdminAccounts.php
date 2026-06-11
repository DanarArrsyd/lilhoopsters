<?php

namespace App\Livewire\Superadmin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class AdminAccounts extends Component
{
    use WithPagination;

    public string $search = '';

    // Create form
    public bool $showForm = false;
    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    // Deactivate confirm
    public bool $showDeactivate = false;
    public ?int $deactivatingId = null;

    public function updatingSearch(): void { $this->resetPage(); }

    public function openForm(): void
    {
        $this->reset(['name', 'email', 'password', 'passwordConfirmation']);
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
    }

    public function create(): void
    {
        $this->validate([
            'name'                 => 'required|string|max:100',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|min:8|same:passwordConfirmation',
            'passwordConfirmation' => 'required',
        ]);

        $adminRole = Role::where('name', 'admin')->firstOrFail();

        User::create([
            'role_id'             => $adminRole->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'password'            => Hash::make($this->password),
            'is_active'           => true,
            'registration_status' => 'approved',
        ]);

        $this->showForm = false;
        session()->flash('success', 'Admin account created.');
    }

    public function toggleActive(int $id): void
    {
        $admin = $this->findAdmin($id);
        $admin->update(['is_active' => !$admin->is_active]);
    }

    public function confirmDeactivate(int $id): void
    {
        $this->deactivatingId = $id;
        $this->showDeactivate = true;
    }

    public function deactivate(): void
    {
        $admin = $this->findAdmin($this->deactivatingId);
        $admin->update(['is_active' => false]);

        $this->showDeactivate = false;
        $this->deactivatingId = null;
        session()->flash('success', 'Admin account deactivated.');
    }

    public function cancelDeactivate(): void
    {
        $this->showDeactivate = false;
        $this->deactivatingId = null;
    }

    private function findAdmin(int $id): User
    {
        return User::whereHas('role', fn($q) => $q->where('name', 'admin'))
            ->findOrFail($id);
    }

    public function render()
    {
        $admins = User::whereHas('role', fn($q) => $q->where('name', 'admin'))
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            }))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.superadmin.admin-accounts', compact('admins'));
    }
}
