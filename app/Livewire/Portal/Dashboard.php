<?php

namespace App\Livewire\Portal;

use App\Models\Enrollment;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();

        $childIds = $user->children()->pluck('id');

        $this->stats = [
            'active_children'     => $user->children()->where('status', 'active')->count(),
            'pending_children'    => $user->children()->where('status', 'pending')->count(),
            'pending_enrollments' => Enrollment::whereIn('child_id', $childIds)->where('status', 'pending')->count(),
            'active_enrollments'  => Enrollment::whereIn('child_id', $childIds)->where('status', 'active')->count(),
            'pending_payments'    => Transaction::where('user_id', $user->id)->where('status', 'pending')->count(),
            'paid_payments'       => Transaction::where('user_id', $user->id)->where('status', 'paid')->count(),
        ];
    }

    public function render()
    {
        return view('livewire.portal.dashboard');
    }
}
