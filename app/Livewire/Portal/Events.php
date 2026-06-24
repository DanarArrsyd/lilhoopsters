<?php

namespace App\Livewire\Portal;

use App\Models\Child;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\EventService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Events extends Component
{
    /** child_id selected per event card. */
    public array $childSelection = [];

    public function register(int $eventId): void
    {
        $childId = $this->childSelection[$eventId] ?? null;
        if (! $childId) {
            session()->flash('error', 'Please choose a child first.');
            return;
        }

        $event = Event::findOrFail($eventId);
        // Ensure the child belongs to this parent.
        $child = Auth::user()->children()->findOrFail($childId);

        try {
            EventService::register($event, $child);
            session()->flash('success', $event->isPaid()
                ? "{$child->name} registered for {$event->name}. Please complete payment on the Payments page."
                : "{$child->name} registered for {$event->name}.");
            unset($this->childSelection[$eventId]);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $user     = Auth::user();
        $childIds = $user->children()->pluck('id');

        $events = Event::query()
            ->where('is_registerable', true)
            ->where('is_active', true)
            ->whereDate('end_date', '>=', today())
            ->withCount(['registrations as taken_count' => fn($q) => $q->where('status', '!=', 'cancelled')])
            ->orderBy('start_date')
            ->get();

        $myRegistrations = EventRegistration::query()
            ->whereIn('child_id', $childIds)
            ->where('status', '!=', 'cancelled')
            ->with(['event', 'child', 'transaction'])
            ->latest('registered_at')
            ->get();

        // Child ids already registered per event (to disable re-register).
        $registeredPairs = $myRegistrations->map(fn($r) => $r->event_id . '-' . $r->child_id)->all();

        return view('livewire.portal.events', [
            'events'          => $events,
            'children'        => $user->children()->where('status', 'active')->orderBy('name')->get(),
            'myRegistrations' => $myRegistrations,
            'registeredPairs' => $registeredPairs,
        ]);
    }
}
