<?php

namespace App\Livewire\Auth;

use App\Models\Child;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class RegisterWizard extends Component
{
    public int $step = 1;
    public int $totalSteps = 3;

    // Step 1: Account
    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Step 2: Parent info
    public string $whatsapp_number = '';
    public string $address         = '';
    public string $occupation      = '';

    // Step 3: First child
    public string $child_name       = '';
    public string $child_birth_date = '';
    public string $child_gender     = '';
    public string $child_school     = '';

    protected function rules(): array
    {
        return match ($this->step) {
            1 => [
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|unique:users,email',
                'password'              => 'required|min:8|confirmed',
                'password_confirmation' => 'required',
            ],
            2 => [
                'whatsapp_number' => 'required|string|max:20',
                'address'         => 'nullable|string|max:500',
                'occupation'      => 'nullable|string|max:100',
            ],
            3 => [
                'child_name'       => 'required|string|max:255',
                'child_birth_date' => 'required|date|before:today',
                'child_gender'     => 'required|in:male,female',
                'child_school'     => 'nullable|string|max:255',
            ],
            default => [],
        };
    }

    public function nextStep(): void
    {
        $this->validate();
        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function prevStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function submit(): void
    {
        $this->validate();

        $parentRole = Role::where('name', 'parent')->firstOrFail();

        $user = User::create([
            'role_id'             => $parentRole->id,
            'name'                => $this->name,
            'email'               => $this->email,
            'password'            => Hash::make($this->password),
            'whatsapp_number'     => $this->whatsapp_number,
            'address'             => $this->address ?: null,
            'occupation'          => $this->occupation ?: null,
            'registration_status' => 'pending',
        ]);

        Child::create([
            'user_id'    => $user->id,
            'name'       => $this->child_name,
            'birth_date' => $this->child_birth_date,
            'gender'     => $this->child_gender,
            'school'     => $this->child_school ?: null,
            'status'     => 'unregistered',
        ]);

        Auth::login($user);

        $this->redirect(route('pending'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.register-wizard');
    }
}
