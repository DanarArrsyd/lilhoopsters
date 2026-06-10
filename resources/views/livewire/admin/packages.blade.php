<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Packages</h2>
            <p class="text-sm text-slate-500">Manage pricing packages per location</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Package</x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search packages..." />
            </div>
            <div class="w-56">
                <select wire:model.live="filterLocation"
                        class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    <option value="">All Locations</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Package</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Location</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Price</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($packages as $package)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-900">{{ $package->name }}</div>
                            @if ($package->is_popular)
                                <span class="text-[10px] font-semibold text-orange-500 uppercase tracking-wide">Popular</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-500">{{ $package->location->name }}</td>
                        <td class="py-3 px-4">
                            <x-badge status="info">{{ str_replace('_', ' ', $package->type) }}</x-badge>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $package->formattedPrice() }}</td>
                        <td class="py-3 px-4">
                            <x-badge :status="$package->is_active ? 'active' : 'inactive'">
                                {{ $package->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2 justify-end">
                                <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $package->id }})">Edit</x-btn>
                                <x-btn variant="ghost" size="sm" wire:click="toggleActive({{ $package->id }})">
                                    {{ $package->is_active ? 'Deactivate' : 'Activate' }}
                                </x-btn>
                                <x-btn variant="ghost" size="sm"
                                       wire:click="confirmDelete({{ $package->id }})"
                                       wire:confirm="Delete this package?">Delete</x-btn>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-2">
                            <x-empty-state title="No packages yet" description="Add your first pricing package." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($packages->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $packages->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 sticky top-0 bg-white">
                    <h3 class="font-semibold text-slate-900">{{ $editingId ? 'Edit Package' : 'Add Package' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Location <span class="text-red-500">*</span></label>
                        <select wire:model="location_id"
                                class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 {{ $errors->has('location_id') ? 'border-red-400' : '' }}">
                            <option value="">Select location...</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('location_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <x-input wire:model="name" label="Package Name" placeholder="e.g. Regular Apr–Mei"
                             required :error="$errors->first('name')" />

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Type <span class="text-red-500">*</span></label>
                        <select wire:model.live="type"
                                class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                            <option value="registration">Registration</option>
                            <option value="regular">Regular</option>
                            <option value="drop_in">Drop-in</option>
                            <option value="private">Private</option>
                        </select>
                    </div>

                    <x-input wire:model="price" type="number" label="Price (Rp)" placeholder="350000"
                             required :error="$errors->first('price')" />

                    @if ($type === 'regular')
                        <div class="grid grid-cols-2 gap-4">
                            <x-input wire:model="period_start" type="date" label="Period Start"
                                     :error="$errors->first('period_start')" />
                            <x-input wire:model="period_end" type="date" label="Period End"
                                     :error="$errors->first('period_end')" />
                        </div>
                        <x-input wire:model="session_count" type="number" label="Session Count"
                                 placeholder="8" :error="$errors->first('session_count')" />
                    @elseif ($type === 'drop_in')
                        <x-input wire:model="validity_days" type="number" label="Validity (days)"
                                 placeholder="30" :error="$errors->first('validity_days')" />
                    @elseif ($type === 'private')
                        <x-input wire:model="session_count" type="number" label="Session Count"
                                 placeholder="Leave blank for unlimited" :error="$errors->first('session_count')" />
                    @endif

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea wire:model="description" rows="2"
                                  class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                                  placeholder="Optional description..."></textarea>
                    </div>

                    <div class="flex gap-6">
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                            Active
                        </label>
                        <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" wire:model="is_popular" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                            Mark as Popular
                        </label>
                    </div>
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
