<?php

namespace App\Livewire\Coach;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Profile extends Component
{
    public string $name           = '';
    public string $email          = '';
    public string $whatsappNumber = '';
    public string $address        = '';
    public string $specialization = '';
    public string $phone          = '';

    public string $currentPassword         = '';
    public string $newPassword             = '';
    public string $newPasswordConfirmation = '';

    public function mount(): void
    {
        $user  = Auth::user();
        $coach = $user->coach;

        $this->name           = $user->name;
        $this->email          = $user->email;
        $this->whatsappNumber = $user->whatsapp_number ?? '';
        $this->address        = $user->address ?? '';
        $this->specialization = $coach?->specialization ?? '';
        $this->phone          = $coach?->phone ?? '';
    }

    public function saveProfile(): void
    {
        $this->validate([
            'name'           => 'required|string|max:100',
            'whatsappNumber' => 'nullable|string|max:20',
            'address'        => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:100',
            'phone'          => 'nullable|string|max:20',
        ]);

        $user = Auth::user();

        $user->update([
            'name'            => $this->name,
            'whatsapp_number' => $this->whatsappNumber ?: null,
            'address'         => $this->address ?: null,
        ]);

        $user->coach?->update([
            'specialization' => $this->specialization ?: null,
            'phone'          => $this->phone ?: null,
        ]);

        session()->flash('profile_success', 'Profile updated.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword'         => 'required',
            'newPassword'             => 'required|string|min:8|same:newPasswordConfirmation',
            'newPasswordConfirmation' => 'required',
        ]);

        $user = Auth::user();

        if ($user->password && !Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user->update(['password' => Hash::make($this->newPassword)]);

        $this->currentPassword         = '';
        $this->newPassword             = '';
        $this->newPasswordConfirmation = '';

        session()->flash('password_success', 'Password changed.');
    }

    public function render()
    {
        return view('livewire.coach.profile');
    }
}
