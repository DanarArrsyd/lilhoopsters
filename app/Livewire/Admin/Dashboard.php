<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public array $stats = [];

    public function mount(): void
    {
        $this->loadStats();
    }

    public function loadStats(): void
    {
        $parentRoleId = Role::where('name', 'parent')->value('id');

        $this->stats = [
            'pending_registrations' => User::where('role_id', $parentRoleId)
                ->where('registration_status', 'pending')
                ->count(),
            'active_players'    => Child::where('status', 'active')->count(),
            'pending_enrollments' => Enrollment::where('status', 'pending')->count(),
            'pending_payments'  => Transaction::where('status', 'pending')->count(),
            'active_locations'  => Location::where('is_active', true)->count(),
            'active_coaches'    => Coach::where('is_active', true)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
