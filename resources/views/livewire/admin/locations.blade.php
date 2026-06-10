<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Locations</h2>
            <p class="text-sm text-slate-500">Manage basketball court locations</p>
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
    <x-card>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Name</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Address</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">City</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($locations as $location)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $location->name }}</td>
                        <td class="py-3 px-4 text-slate-500 max-w-xs truncate">{{ $location->address }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $location->city }}</td>
                        <td class="py-3 px-4">
                            <x-badge :status="$location->is_active ? 'active' : 'inactive'">
                                {{ $location->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2 justify-end">
                                <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $location->id }})">Edit</x-btn>
                                <x-btn variant="ghost" size="sm" wire:click="toggleActive({{ $location->id }})">
                                    {{ $location->is_active ? 'Deactivate' : 'Activate' }}
                                </x-btn>
                                <x-btn variant="ghost" size="sm"
                                       wire:click="confirmDelete({{ $location->id }})"
                                       wire:confirm="Delete this location?">Delete</x-btn>
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
        @if ($locations->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $locations->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">{{ $editingId ? 'Edit Location' : 'Add Location' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
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
                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                        Active
                    </label>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1 justify-center" wire:click="$set('showModal', false)">Cancel</x-btn>
                    <x-btn class="flex-1 justify-center" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
