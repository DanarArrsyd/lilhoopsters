<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Locations</h2>
        <p class="text-sm text-muted">Manage basketball court locations.</p>
    </div>
    <button wire:click="openCreate"
            class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Search --}}
    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search locations..." />
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Name</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Address</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">City</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($locations as $location)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $location->name }}</td>
                            <td class="py-3 px-4 text-muted max-w-xs truncate">{{ $location->address }}</td>
                            <td class="py-3 px-4 text-muted">{{ $location->city }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$location->is_active ? 'active' : 'inactive'">
                                    {{ $location->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="edit" size="sm" wire:click="openEdit({{ $location->id }})">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        Edit
                                    </x-btn>
                                    <x-btn variant="{{ $location->is_active ? 'warning' : 'success' }}" size="sm"
                                           wire:click="toggleActive({{ $location->id }})"
                                           wire:loading.attr="disabled">
                                        @if ($location->is_active)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Deactivate
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                            Activate
                                        @endif
                                    </x-btn>
                                    <x-btn variant="danger" size="sm"
                                           wire:click="confirmDelete({{ $location->id }})"
                                           wire:confirm="Delete this location?"
                                           wire:loading.attr="disabled">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Delete
                                    </x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-2">
                                <x-empty-state title="No locations yet" description="Add your first basketball court location." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($locations->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $locations->links() }}</div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? 'Edit Location' : 'New Location' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="name" label="Location Name" placeholder="e.g. Pakubuwono Court"
                             required :error="$errors->first('name')" />
                    <x-input wire:model="address" label="Address" placeholder="Full address"
                             required :error="$errors->first('address')" />
                    <x-input wire:model="city" label="City" placeholder="Jakarta"
                             required :error="$errors->first('city')" />
                    <x-input wire:model="maps_url" label="Google Maps URL" placeholder="https://maps.google.com/..."
                             :error="$errors->first('maps_url')" />
                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded accent-navy">
                        Active
                    </label>
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
