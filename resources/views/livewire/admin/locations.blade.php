<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Locations</h2>
            <p class="text-sm text-muted">Manage basketball court locations.</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Location</x-btn>
    </div>

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
                                    <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $location->id }})">Edit</x-btn>
                                    <x-btn variant="ghost" size="sm" wire:click="toggleActive({{ $location->id }})"
                                           wire:loading.attr="disabled">
                                        {{ $location->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-btn>
                                    <x-btn variant="ghost" size="sm"
                                           wire:click="confirmDelete({{ $location->id }})"
                                           wire:confirm="Delete this location?"
                                           wire:loading.attr="disabled">Delete</x-btn>
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
                    <x-btn variant="secondary" class="flex-1" wire:click="$set('showModal', false)">Cancel</x-btn>
                    <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
