<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Events</h2>
        <p class="text-sm text-muted">Pause regular classes for an event — members' packages are auto-extended.</p>
    </div>

    <button wire:click="openCreate"
            class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search events..." />
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[760px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Event</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Period</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Scope</th>
                        <th class="text-right py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Frozen</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($events as $event)
                        @php
                            $days = $event->start_date->diffInDays($event->end_date) + 1;
                            if (! $event->is_active)                       $state = ['Inactive', 'bg-line text-faint'];
                            elseif (today()->lt($event->start_date))       $state = ['Upcoming', 'bg-[#1D4ED8]/10 text-[#1D4ED8]'];
                            elseif (today()->gt($event->end_date))         $state = ['Past', 'bg-navy/10 text-navy'];
                            else                                           $state = ['Running', 'bg-[#15803D]/10 text-[#15803D]'];
                        @endphp
                        <tr class="hover:bg-off transition-colors" wire:key="event-{{ $event->id }}">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $event->name }}</p>
                                @if ($event->description)
                                    <p class="text-xs text-muted max-w-xs truncate">{{ $event->description }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ $event->start_date->format('d M Y') }} – {{ $event->end_date->format('d M Y') }}
                                <span class="block text-xs text-faint">{{ $days }} days</span>
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ $event->location?->name ?? 'All locations' }}
                                <span class="block text-xs text-faint">{{ $event->program?->name ?? 'All programs' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span class="font-bold text-navy">{{ $event->enrollments_count }}</span>
                                <span class="block text-xs text-faint">packages</span>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex text-[11px] font-bold px-2 py-0.5 rounded-md {{ $state[1] }}">{{ $state[0] }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="edit" size="sm" wire:click="openEdit({{ $event->id }})" wire:loading.attr="disabled">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        Edit
                                    </x-btn>
                                    <x-btn variant="{{ $event->is_active ? 'warning' : 'success' }}" size="sm"
                                           wire:click="toggleActive({{ $event->id }})" wire:loading.attr="disabled"
                                           wire:target="toggleActive({{ $event->id }})">
                                        {{ $event->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-btn>
                                    <x-btn variant="danger" size="sm"
                                           wire:click="confirmDelete({{ $event->id }})"
                                           wire:confirm="Delete this event? Frozen packages will be reverted."
                                           wire:loading.attr="disabled" wire:target="confirmDelete({{ $event->id }})">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state title="No events yet" description="Create an event to pause classes and auto-extend member packages." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($events->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $events->links() }}</div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-surface">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? 'Edit Event' : 'New Event' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="name" label="Event Name" placeholder="e.g. Summer Champ"
                             required :error="$errors->first('name')" />

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Description</label>
                        <textarea wire:model="description" rows="2" aria-label="Event description"
                                  class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                  placeholder="Optional details shown to parents..."></textarea>
                        @error('description') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input wire:model="start_date" type="date" label="Start Date" required :error="$errors->first('start_date')" />
                        <x-input wire:model="end_date" type="date" label="End Date" required :error="$errors->first('end_date')" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Location</label>
                            <x-select wire:model="location_id">
                                <option value="">All locations</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Program</label>
                            <x-select wire:model="program_id">
                                <option value="">All programs</option>
                                @foreach ($programs as $prog)
                                    <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded accent-navy">
                        Active (applies the package freeze)
                    </label>

                    <p class="text-xs text-muted bg-off border border-line rounded-lg px-3 py-2">
                        When active, members in scope have their package expiry pushed back by the event length, and the event dates are skipped on their attendance calendar.
                    </p>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1" wire:click="$set('showModal', false)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Cancel
                    </x-btn>
                    <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Save' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
