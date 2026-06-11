<?php

namespace App\Livewire\Superadmin;

use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
use App\Models\User;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $adminRoleId = \App\Models\Role::where('name', 'admin')->value('id');

        $stats = [
            'total_admins'   => User::where('role_id', $adminRoleId)->count(),
            'active_admins'  => User::where('role_id', $adminRoleId)->where('is_active', true)->count(),
            'total_coaches'  => Coach::count(),
            'total_parents'  => User::whereHas('role', fn($q) => $q->where('name', 'parent'))->count(),
            'total_players'  => Child::count(),
            'active_players' => Child::where('status', 'active')->count(),
            'enrollments'    => Enrollment::where('status', 'approved')->count(),
        ];

        return view('livewire.superadmin.dashboard', compact('stats'));
    }
}
