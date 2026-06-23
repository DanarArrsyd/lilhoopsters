<?php

namespace App\Livewire\Admin;

use App\Models\Program;
use Livewire\Component;
use Livewire\WithPagination;

class Programs extends Component
{
    use WithPagination;

    public string $search       = '';
    public bool $showModal      = false;
    public ?int $editingId      = null;

    public string $name        = '';
    public int $minAgeYears    = 1;
    public int $maxAgeYears    = 7;
    public string $description = '';
    public bool $is_active     = true;

    protected function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'minAgeYears' => 'required|integer|min:1|max:21',
            'maxAgeYears' => 'required|integer|min:1|max:21|gte:minAgeYears',
            'description' => 'nullable|string|max:1000',
        ];
    }

    protected $messages = [
        'maxAgeYears.gte' => 'Max age must be greater than or equal to min age.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $program = Program::findOrFail($id);
        $this->editingId    = $id;
        $this->name         = $program->name;
        $this->minAgeYears  = (int) round($program->min_age_months / 12);
        $this->maxAgeYears  = (int) round($program->max_age_months / 12);
        $this->description  = $program->description ?? '';
        $this->is_active    = $program->is_active;
        $this->showModal    = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'           => $this->name,
            'min_age_months' => $this->minAgeYears * 12,
            'max_age_months' => $this->maxAgeYears * 12,
            'description'    => $this->description ?: null,
            'is_active'      => $this->is_active,
        ];

        if ($this->editingId) {
            Program::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Program updated.');
        } else {
            Program::create($data);
            session()->flash('success', 'Program created.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $program = Program::findOrFail($id);
        $program->update(['is_active' => ! $program->is_active]);
    }

    public function confirmDelete(int $id): void
    {
        Program::findOrFail($id)->delete();
        session()->flash('success', 'Program deleted.');
    }

    public function resetForm(): void
    {
        $this->editingId   = null;
        $this->name        = '';
        $this->minAgeYears = 1;
        $this->maxAgeYears = 7;
        $this->description = '';
        $this->is_active   = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.programs', [
            'programs' => Program::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderBy('min_age_months')
                ->paginate(10),
        ]);
    }
}
