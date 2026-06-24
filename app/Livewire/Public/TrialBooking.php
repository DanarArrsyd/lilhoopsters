<?php

namespace App\Livewire\Public;

use App\Models\Lead;
use App\Models\Location;
use App\Models\Program;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.app', ['title' => 'Book a Free Trial'])]
class TrialBooking extends Component
{
    public string $parent_name = '';
    public string $whatsapp     = '';
    public string $child_name   = '';
    public string $child_age    = '';
    public ?int $location_id    = null;
    public ?int $program_id     = null;
    public string $trial_date   = '';
    public string $notes        = '';

    /** Honeypot — real users never see/fill this; bots do. */
    public string $website = '';

    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'parent_name' => 'required|string|max:255',
            'whatsapp'    => 'required|string|max:30',
            'child_name'  => 'required|string|max:255',
            'child_age'   => 'nullable|integer|min:1|max:21',
            'location_id' => 'nullable|exists:locations,id',
            'program_id'  => 'nullable|exists:programs,id',
            'trial_date'  => 'nullable|date|after_or_equal:today',
            'notes'       => 'nullable|string|max:1000',
        ];
    }

    public function submit(): void
    {
        // Bot filled the honeypot → pretend success, save nothing.
        if ($this->website !== '') {
            $this->submitted = true;
            return;
        }

        // Throttle: 5 submissions per hour per IP.
        $key = 'trial:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('parent_name', 'Too many requests. Please try again later.');
            return;
        }

        $this->validate();
        RateLimiter::hit($key, 3600);

        $notes = trim(
            ($this->child_age ? "Child age: {$this->child_age}. " : '')
            . $this->notes
        );

        Lead::create([
            'parent_name' => $this->parent_name,
            'child_name'  => $this->child_name,
            'whatsapp'    => $this->whatsapp,
            'source'      => 'web',
            'status'      => 'new',
            'location_id' => $this->location_id ?: null,
            'program_id'  => $this->program_id ?: null,
            'trial_date'  => $this->trial_date ?: null,
            'notes'       => $notes ?: null,
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.public.trial-booking', [
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'programs'  => Program::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
