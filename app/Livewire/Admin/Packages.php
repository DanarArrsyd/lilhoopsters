<?php

namespace App\Livewire\Admin;

use App\Models\Location;
use App\Models\Package;
use Livewire\Component;
use Livewire\WithPagination;

class Packages extends Component
{
    use WithPagination;

    public string $search         = '';
    public ?int $filterLocation   = null;
    public bool $showModal        = false;
    public ?int $editingId        = null;

    public ?int $location_id      = null;
    public string $name           = '';
    public string $type           = 'registration';
    public int $price             = 0;
    public ?int $session_count    = null;
    public ?int $validity_days    = null;
    public string $period_start   = '';
    public string $period_end     = '';
    public string $description    = '';
    public bool $is_active        = true;
    public bool $is_popular       = false;

    protected function rules(): array
    {
        return [
            'location_id'   => 'required|integer|exists:locations,id',
            'name'          => 'required|string|max:255',
            'type'          => 'required|in:registration,regular,drop_in,private',
            'price'         => 'required|integer|min:1',
            'session_count' => 'nullable|integer|min:1',
            'validity_days' => $this->type === 'regular' ? 'required|integer|min:1' : 'nullable|integer|min:1',
            'description'   => 'nullable|string|max:1000',
        ];
    }

    public function updatingSearch(): void        { $this->resetPage(); }
    public function updatingFilterLocation(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $pkg = Package::findOrFail($id);
        $this->editingId     = $id;
        $this->location_id   = $pkg->location_id;
        $this->name          = $pkg->name;
        $this->type          = $pkg->type;
        $this->price         = $pkg->price;
        $this->session_count = $pkg->session_count;
        $this->validity_days = $pkg->validity_days;
        $this->period_start  = $pkg->period_start?->format('Y-m-d') ?? '';
        $this->period_end    = $pkg->period_end?->format('Y-m-d') ?? '';
        $this->description   = $pkg->description ?? '';
        $this->is_active     = $pkg->is_active;
        $this->is_popular    = $pkg->is_popular;
        $this->showModal     = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'location_id'   => $this->location_id,
            'name'          => $this->name,
            'type'          => $this->type,
            'price'         => $this->price,
            'session_count' => $this->session_count,
            'validity_days' => $this->validity_days,
            // Regular packages use validity_days; period_start/end are not used
            'period_start'  => $this->type === 'regular' ? null : ($this->period_start ?: null),
            'period_end'    => $this->type === 'regular' ? null : ($this->period_end ?: null),
            'description'   => $this->description ?: null,
            'is_active'     => $this->is_active,
            'is_popular'    => $this->is_popular,
        ];

        if ($this->editingId) {
            Package::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Package updated.');
        } else {
            Package::create($data);
            session()->flash('success', 'Package created.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $pkg = Package::findOrFail($id);
        $pkg->update(['is_active' => ! $pkg->is_active]);
    }

    public function confirmDelete(int $id): void
    {
        Package::findOrFail($id)->delete();
        session()->flash('success', 'Package deleted.');
    }

    public function resetForm(): void
    {
        $this->editingId     = null;
        $this->location_id   = null;
        $this->name          = '';
        $this->type          = 'registration';
        $this->price         = 0;
        $this->session_count = null;
        $this->validity_days = null;
        $this->period_start  = '';
        $this->period_end    = '';
        $this->description   = '';
        $this->is_active     = true;
        $this->is_popular    = false;
        $this->resetValidation();
    }

    public function render()
    {
        $typeOrder = ['registration' => 1, 'regular' => 2, 'drop_in' => 3, 'private' => 4];

        $packages = Package::with('location')
            ->when($this->filterLocation, fn($q) => $q->where('location_id', $this->filterLocation))
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->get();

        // Group by location, ordered by type then price within each location.
        $groups = $packages
            ->groupBy('location_id')
            ->map(fn($items) => $items
                ->sortBy(fn($p) => sprintf('%d-%012d', $typeOrder[$p->type] ?? 9, $p->price))
                ->values())
            ->sortBy(fn($items) => optional($items->first()->location)->name)
            ->values();

        return view('livewire.admin.packages', [
            'groups'        => $groups,
            'totalCount'    => $packages->count(),
            'locationCount' => $groups->count(),
            'locations'     => Location::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
