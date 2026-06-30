<?php

namespace App\Livewire\Admin;

use App\Models\Coach;
use App\Models\Location;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;

class Coaches extends Component
{
    use WithPagination;

    public string $search           = '';
    public bool $showModal          = false;
    public ?int $editingId          = null;

    public string $coach_name       = '';
    public string $coach_email      = '';
    public string $coach_password   = '';
    public string $phone            = '';
    public string $specialization   = '';
    public bool $is_active          = true;
    public array $selectedLocations = [];
    public int   $step              = 1;

    protected function rules(): array
    {
        $emailRule = $this->editingId
            ? 'required|email|unique:users,email,' . $this->coachUserId()
            : 'required|email|unique:users,email';

        return [
            'coach_name'     => 'required|string|max:255',
            'coach_email'    => $emailRule,
            'coach_password' => $this->editingId ? 'nullable|min:8' : 'required|min:8',
            'phone'          => 'required|string|max:20',
            'specialization' => 'nullable|string|max:255',
        ];
    }

    private function coachUserId(): ?int
    {
        if (! $this->editingId) return null;
        return Coach::find($this->editingId)?->user_id;
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validateOnly(['coach_name', 'coach_email', 'coach_password']);
            $this->step = 2;
        }
    }

    public function prevStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function toggleLocation(int $id): void
    {
        if (in_array($id, $this->selectedLocations, true)) {
            $this->selectedLocations = array_values(
                array_filter($this->selectedLocations, fn($l) => $l !== $id)
            );
        } else {
            $this->selectedLocations[] = $id;
        }
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $coach = Coach::with(['user', 'locations'])->findOrFail($id);
        $this->editingId          = $id;
        $this->coach_name         = $coach->user->name;
        $this->coach_email        = $coach->user->email;
        $this->coach_password     = '';
        $this->phone              = $coach->phone;
        $this->specialization     = $coach->specialization ?? '';
        $this->is_active          = $coach->is_active;
        $this->selectedLocations  = $coach->locations->pluck('id')->toArray();
        $this->step               = 1;
        $this->showModal          = true;
    }

    public function save(): void
    {
        $this->validate();

        DB::transaction(function () {
            if ($this->editingId) {
                $coach = Coach::with('user')->findOrFail($this->editingId);

                $coach->user->update([
                    'name'  => $this->coach_name,
                    'email' => $this->coach_email,
                    ...($this->coach_password ? ['password' => Hash::make($this->coach_password)] : []),
                ]);

                $coach->update([
                    'phone'          => $this->phone,
                    'specialization' => $this->specialization ?: null,
                    'is_active'      => $this->is_active,
                ]);

                $coach->locations()->sync($this->selectedLocations);

                session()->flash('success', 'Coach updated.');
            } else {
                $role = Role::where('name', 'coach')->firstOrFail();

                $user = User::create([
                    'role_id'             => $role->id,
                    'name'                => $this->coach_name,
                    'email'               => $this->coach_email,
                    'password'            => Hash::make($this->coach_password),
                    'registration_status' => 'approved',
                    'is_active'           => true,
                ]);

                $coach = Coach::create([
                    'user_id'        => $user->id,
                    'phone'          => $this->phone,
                    'specialization' => $this->specialization ?: null,
                    'is_active'      => $this->is_active,
                ]);

                $coach->locations()->sync($this->selectedLocations);

                session()->flash('success', 'Coach created.');
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $coach = Coach::findOrFail($id);
        $coach->update(['is_active' => ! $coach->is_active]);
    }

    public function resetForm(): void
    {
        $this->step               = 1;
        $this->editingId          = null;
        $this->coach_name         = '';
        $this->coach_email        = '';
        $this->coach_password     = '';
        $this->phone              = '';
        $this->specialization     = '';
        $this->is_active          = true;
        $this->selectedLocations  = [];
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.coaches', [
            'coaches'   => Coach::with(['user', 'locations'])
                ->when($this->search, fn($q) => $q->whereHas('user', fn($u) =>
                    $u->where('name', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%")))
                ->orderBy('id', 'desc')
                ->paginate(10),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
