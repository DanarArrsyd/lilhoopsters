<?php

namespace App\Livewire\Portal;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CompleteProfile extends Component
{
    public int    $step       = 1;
    public int    $totalSteps = 4;

    public string $name           = '';
    public string $whatsappNumber = '';
    public string $address        = '';
    public string $occupation     = '';

    public function mount(): void
    {
        $this->name = Auth::user()->name ?? '';
    }

    protected function rules(): array
    {
        return match ($this->step) {
            1 => ['name'           => 'required|string|min:2|max:100'],
            2 => ['whatsappNumber' => ['required', 'string', 'regex:/^[0-9]{9,15}$/']],
            3 => ['address'        => 'nullable|string|max:500'],
            4 => ['occupation'     => 'nullable|string|max:100'],
            default => [],
        };
    }

    protected function messages(): array
    {
        return [
            'name.required'           => 'Full name is required.',
            'name.min'                => 'Name must be at least 2 characters.',
            'whatsappNumber.required' => 'WhatsApp number is required.',
            'whatsappNumber.regex'    => 'Invalid format. Example: 628123456789',
        ];
    }

    public function nextStep(): void
    {
        $this->validate();
        $this->step++;
    }

    public function prevStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function save(): void
    {
        $this->validate();

        Auth::user()->update([
            'name'            => $this->name,
            'whatsapp_number' => $this->whatsappNumber,
            'address'         => $this->address ?: null,
            'occupation'      => $this->occupation ?: null,
        ]);

        $this->redirect(route('parent.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.portal.complete-profile')
            ->layout('components.app', ['title' => 'Complete Your Profile']);
    }
}
