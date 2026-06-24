<?php

namespace App\Livewire\Admin;

use App\Models\Child;
use App\Models\Event;
use App\Models\Location;
use App\Models\Program;
use App\Services\EventService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class Events extends Component
{
    use WithPagination;

    public string $search    = '';
    public bool $showModal   = false;
    public ?int $editingId   = null;

    public string $name           = '';
    public string $description    = '';
    public string $start_date     = '';
    public string $end_date       = '';
    public ?int $location_id      = null;
    public ?int $program_id       = null;
    public bool $is_active         = true;
    public bool $is_registerable   = false;
    public ?int $price            = null;
    public ?int $capacity         = null;

    // Participant management panel
    public ?int $managingEventId  = null;
    public ?int $addChildId       = null;

    protected function rules(): array
    {
        return [
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string|max:1000',
            'start_date'      => 'required|date',
            'end_date'        => 'required|date|after_or_equal:start_date',
            'location_id'     => 'nullable|exists:locations,id',
            'program_id'      => 'nullable|exists:programs,id',
            'price'           => 'nullable|integer|min:0',
            'capacity'        => 'nullable|integer|min:1',
        ];
    }

    public function updatingSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $event = Event::findOrFail($id);
        $this->editingId   = $id;
        $this->name        = $event->name;
        $this->description = $event->description ?? '';
        $this->start_date      = $event->start_date->toDateString();
        $this->end_date        = $event->end_date->toDateString();
        $this->location_id     = $event->location_id;
        $this->program_id      = $event->program_id;
        $this->is_active       = $event->is_active;
        $this->is_registerable = $event->is_registerable;
        $this->price           = $event->price;
        $this->capacity        = $event->capacity;
        $this->showModal       = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'start_date'  => $this->start_date,
            'end_date'    => $this->end_date,
            'location_id' => $this->location_id ?: null,
            'program_id'  => $this->program_id ?: null,
            'is_active'   => $this->is_active,
            'is_registerable' => $this->is_registerable,
            'price'           => $this->is_registerable ? ($this->price ?: null) : null,
            'capacity'        => $this->is_registerable ? ($this->capacity ?: null) : null,
        ];

        DB::transaction(function () use ($data) {
            if ($this->editingId) {
                $event = Event::findOrFail($this->editingId);
                EventService::reverseFreeze($event);   // undo old freeze first
                $event->update($data);
                EventService::applyFreeze($event);      // re-apply with new dates/scope
            } else {
                $data['created_by'] = auth()->id();
                $event = Event::create($data);
                EventService::applyFreeze($event);
            }
        });

        session()->flash('success', $this->editingId ? 'Event updated.' : 'Event created.');
        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $event = Event::findOrFail($id);

        DB::transaction(function () use ($event) {
            $event->update(['is_active' => ! $event->is_active]);
            $event->is_active
                ? EventService::applyFreeze($event)
                : EventService::reverseFreeze($event);
        });
    }

    public function confirmDelete(int $id): void
    {
        $event = Event::findOrFail($id);

        DB::transaction(function () use ($event) {
            EventService::reverseFreeze($event);
            $event->delete();
        });

        session()->flash('success', 'Event deleted.');
    }

    public function resetForm(): void
    {
        $this->editingId   = null;
        $this->name        = '';
        $this->description = '';
        $this->start_date  = '';
        $this->end_date    = '';
        $this->location_id     = null;
        $this->program_id      = null;
        $this->is_active       = true;
        $this->is_registerable = false;
        $this->price           = null;
        $this->capacity        = null;
        $this->resetValidation();
    }

    // ─── Participant management ──────────────────────────────────────

    public function openParticipants(int $id): void
    {
        $this->managingEventId = $id;
        $this->addChildId      = null;
    }

    public function closeParticipants(): void
    {
        $this->managingEventId = null;
        $this->addChildId      = null;
    }

    public function addParticipant(): void
    {
        if (! $this->managingEventId || ! $this->addChildId) {
            return;
        }

        $event = Event::findOrFail($this->managingEventId);
        $child = Child::findOrFail($this->addChildId);

        try {
            EventService::register($event, $child);
            session()->flash('success', "{$child->name} registered" . ($event->isPaid() ? ' (awaiting payment).' : '.'));
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }

        $this->addChildId = null;
    }

    public function cancelRegistration(int $registrationId): void
    {
        $reg = \App\Models\EventRegistration::where('event_id', $this->managingEventId)
            ->findOrFail($registrationId);
        $reg->update(['status' => 'cancelled']);
        session()->flash('success', 'Registration cancelled.');
    }

    public function render()
    {
        $managingEvent     = null;
        $availableChildren = collect();

        if ($this->managingEventId) {
            $managingEvent = Event::with([
                'registrations.child.parent',
                'registrations.transaction',
            ])->find($this->managingEventId);

            if ($managingEvent) {
                $taken = $managingEvent->registrations
                    ->where('status', '!=', 'cancelled')
                    ->pluck('child_id')->all();

                $availableChildren = Child::query()
                    ->where('status', 'active')
                    ->whereNotIn('id', $taken)
                    ->orderBy('name')
                    ->get();
            }
        }

        return view('livewire.admin.events', [
            'events' => Event::query()
                ->with(['location', 'program'])
                ->withCount(['enrollments', 'registrations as registrations_count' => fn($q) => $q->where('status', '!=', 'cancelled')])
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                ->orderByDesc('start_date')
                ->paginate(10),
            'locations'         => Location::where('is_active', true)->orderBy('name')->get(),
            'programs'          => Program::where('is_active', true)->orderBy('name')->get(),
            'managingEvent'     => $managingEvent,
            'availableChildren' => $availableChildren,
        ]);
    }
}
