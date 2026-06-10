<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use Livewire\Component;
use Livewire\WithPagination;

class Locations extends Component
{
    use WithPagination;

    public string $search   = '';
    public bool $showModal  = false;
    public ?int $editingId  = null;

    public string $name     = '';
    public string $address  = '';
    public string $city     = 'Jakarta';
    public string $maps_url = '';
    public bool $is_active  = true;

    protected function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'address'  => 'required|string|max:500',
            'city'     => 'required|string|max:100',
            'maps_url' => 'nullable|url|max:500',
        ];
    }

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
        $loc = Location::findOrFail($id);
        $this->editingId = $id;
        $this->name      = $loc->name;
        $this->address   = $loc->address;
        $this->city      = $loc->city;
        $this->maps_url  = $loc->maps_url ?? '';
        $this->is_active = $loc->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'      => $this->name,
            'address'   => $this->address,
            'city'      => $this->city,
            'maps_url'  => $this->maps_url ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Location::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Location updated.');
        } else {
            Location::create($data);
            session()->flash('success', 'Location created.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $loc = Location::findOrFail($id);
        $loc->update(['is_active' => ! $loc->is_active]);
    }

    public function confirmDelete(int $id): void
    {
        Location::findOrFail($id)->delete();
        session()->flash('success', 'Location deleted.');
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->name      = '';
        $this->address   = '';
        $this->city      = 'Jakarta';
        $this->maps_url  = '';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.locations', [
            'locations' => Location::query()
                ->when($this->search, fn($q) => $q
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('city', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate(10),
        ]);
    }
}
