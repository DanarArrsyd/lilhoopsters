<?php

namespace App\Livewire\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class RegisterWizard extends Component
{
    public int $step      = 1;
    public int $totalSteps = 6;

    public string $name                  = '';
    public string $email                 = '';
    public string $password              = '';
    public string $password_confirmation = '';
    public string $whatsapp_number       = '';
    public string $address               = '';
    public string $occupation            = '';

    protected function rules(): array
    {
        return match ($this->step) {
            1 => ['name'  => 'required|string|max:255'],
            2 => ['email' => 'required|email|unique:users,email'],
            3 => [
                'password'              => 'required|min:8|confirmed',
                'password_confirmation' => 'required',
            ],
            4 => ['whatsapp_number' => ['required', 'string', 'regex:/^[0-9]{9,15}$/']],
            5 => ['address'    => 'nullable|string|max:500'],
            6 => ['occupation' => 'nullable|string|max:100'],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'name.required'                  => 'Full name is required.',
            'name.max'                       => 'Name may not exceed 255 characters.',
            'email.required'                 => 'Email address is required.',
            'email.email'                    => 'Please enter a valid email address.',
            'email.unique'                   => 'This email is already registered.',
            'password.required'              => 'Password is required.',
            'password.min'                   => 'Password must be at least 8 characters.',
            'password.confirmed'             => 'Passwords do not match.',
            'password_confirmation.required' => 'Please confirm your password.',
            'whatsapp_number.required'       => 'WhatsApp number is required.',
            'whatsapp_number.regex'          => 'Invalid format. Example: 628123456789',
        ];
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
            'is_active'           => true,
        ]);

        Auth::login($user);

        $this->redirect(route('pending'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.register-wizard')
            ->layout('components.app', ['title' => 'Create Account']);
    }
}
