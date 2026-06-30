<?php

namespace App\Livewire\Admin;

use App\Models\Lead;
use App\Models\Location;
use App\Models\Program;
use Livewire\Component;
use Livewire\WithPagination;

class Leads extends Component
{
    use WithPagination;

    public string $search       = '';
    public string $statusFilter = '';
    public bool $showModal       = false;
    public ?int $editingId       = null;

    // Form
    public string $parent_name = '';
    public string $child_name  = '';
    public string $whatsapp    = '';
    public string $source      = 'other';
    public ?int $location_id   = null;
    public ?int $program_id    = null;
    public string $status      = 'new';
    public string $trial_date  = '';
    public string $notes       = '';
    public int    $step        = 1;

    protected function rules(): array
    {
        return [
            'parent_name' => 'required|string|max:255',
            'child_name'  => 'nullable|string|max:255',
            'whatsapp'    => 'nullable|string|max:30',
            'source'      => 'required|in:' . implode(',', Lead::SOURCES),
            'location_id' => 'nullable|exists:locations,id',
            'program_id'  => 'nullable|exists:programs,id',
            'status'      => 'required|in:' . implode(',', Lead::STATUSES),
            'trial_date'  => 'nullable|date',
            'notes'       => 'nullable|string|max:1000',
        ];
    }

    public function updatingSearch(): void       { $this->resetPage(); }
    public function updatingStatusFilter(): void { $this->resetPage(); }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validateOnly(['parent_name']);
            $this->step = 2;
        }
    }

    public function prevStep(): void
    {
        if ($this->step > 1) $this->step--;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $lead = Lead::findOrFail($id);
        $this->editingId   = $id;
        $this->parent_name = $lead->parent_name;
        $this->child_name  = $lead->child_name ?? '';
        $this->whatsapp    = $lead->whatsapp ?? '';
        $this->source      = $lead->source;
        $this->location_id = $lead->location_id;
        $this->program_id  = $lead->program_id;
        $this->status      = $lead->status;
        $this->trial_date  = $lead->trial_date?->toDateString() ?? '';
        $this->notes       = $lead->notes ?? '';
        $this->step        = 1;
        $this->showModal   = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'parent_name' => $this->parent_name,
            'child_name'  => $this->child_name ?: null,
            'whatsapp'    => $this->whatsapp ?: null,
            'source'      => $this->source,
            'location_id' => $this->location_id ?: null,
            'program_id'  => $this->program_id ?: null,
            'status'      => $this->status,
            'trial_date'  => $this->trial_date ?: null,
            'notes'       => $this->notes ?: null,
        ];

        if ($this->editingId) {
            Lead::findOrFail($this->editingId)->update($data);
            session()->flash('success', 'Lead updated.');
        } else {
            $data['created_by'] = auth()->id();
            Lead::create($data);
            session()->flash('success', 'Lead added.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    /** Quick status change from the row dropdown. */
    public function setStatus(int $id, string $status): void
    {
        if (! in_array($status, Lead::STATUSES, true)) {
            return;
        }
        Lead::findOrFail($id)->update(['status' => $status]);
        session()->flash('success', 'Status updated.');
    }

    public function confirmDelete(int $id): void
    {
        Lead::findOrFail($id)->delete();
        session()->flash('success', 'Lead deleted.');
    }

    public function resetForm(): void
    {
        $this->step        = 1;
        $this->editingId   = null;
        $this->parent_name = '';
        $this->child_name  = '';
        $this->whatsapp    = '';
        $this->source      = 'other';
        $this->location_id = null;
        $this->program_id  = null;
        $this->status      = 'new';
        $this->trial_date  = '';
        $this->notes       = '';
        $this->resetValidation();
    }

    public function render()
    {
        $leads = Lead::query()
            ->with(['location', 'program'])
            ->when($this->search, fn($q) => $q->where(fn($w) => $w
                ->where('parent_name', 'like', "%{$this->search}%")
                ->orWhere('child_name', 'like', "%{$this->search}%")
                ->orWhere('whatsapp', 'like', "%{$this->search}%")))
            ->when($this->statusFilter, fn($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        $counts = Lead::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        return view('livewire.admin.leads', [
            'leads'     => $leads,
            'counts'    => $counts,
            'total'     => array_sum($counts),
            'locations' => Location::where('is_active', true)->orderBy('name')->get(),
            'programs'  => Program::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
