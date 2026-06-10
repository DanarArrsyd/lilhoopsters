<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Packages</h2>
            <p class="text-sm text-muted">Manage pricing packages per location.</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Package</x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search packages..." />
            </div>
            <div class="w-full sm:w-56">
                <x-select wire:model.live="filterLocation">
                    <option value="">All Locations</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="border-b border-line">
                    <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Package</th>
                    <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Location</th>
                    <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Type</th>
                    <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Price</th>
                    <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($packages as $package)
                    <tr class="hover:bg-off transition-colors">
                        <td class="py-3 px-4">
                            <div class="font-semibold text-ink">{{ $package->name }}</div>
                            @if ($package->is_popular)
                                <span class="text-[10px] font-semibold text-[#B45309] uppercase tracking-wide">Popular</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-muted">{{ $package->location->name }}</td>
                        <td class="py-3 px-4">
                            <x-badge status="info">{{ str_replace('_', ' ', $package->type) }}</x-badge>
                        </td>
                        <td class="py-3 px-4 font-semibold text-ink">{{ $package->formattedPrice() }}</td>
                        <td class="py-3 px-4">
                            <x-badge :status="$package->is_active ? 'active' : 'inactive'">
                                {{ $package->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2 justify-end">
                                <x-btn variant="ghost" size="sm"
                                       wire:click="openEdit({{ $package->id }})"
                                       wire:loading.attr="disabled">Edit</x-btn>
                                <x-btn variant="ghost" size="sm"
                                       wire:click="toggleActive({{ $package->id }})"
                                       wire:loading.attr="disabled">
                                    {{ $package->is_active ? 'Deactivate' : 'Activate' }}
                                </x-btn>
                                <x-btn variant="ghost" size="sm"
                                       wire:click="confirmDelete({{ $package->id }})"
                                       wire:confirm="Delete this package?"
                                       wire:loading.attr="disabled">Delete</x-btn>
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
        </div>
        @if ($packages->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $packages->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? 'Edit Package' : 'New Package' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model="location_id" label="Location" :error="$errors->first('location_id')">
                    <option value="">Select location...</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>

                <x-input wire:model="name" label="Package Name" placeholder="e.g. Regular Apr–Mei" required :error="$errors->first('name')" />

                <x-select wire:model.live="type" label="Type" :error="$errors->first('type')">
                    <option value="registration">Registration</option>
                    <option value="regular">Regular</option>
                    <option value="drop_in">Drop-in</option>
                    <option value="private">Private</option>
                </x-select>

                <x-input wire:model="price" type="number" label="Price (Rp)" placeholder="350000" required :error="$errors->first('price')" />

                @if ($type === 'regular')
                    <div class="grid grid-cols-2 gap-4">
                        <x-input wire:model="period_start" type="date" label="Period Start" :error="$errors->first('period_start')" />
                        <x-input wire:model="period_end" type="date" label="Period End" :error="$errors->first('period_end')" />
                    </div>
                    <x-input wire:model="session_count" type="number" label="Session Count" placeholder="8" :error="$errors->first('session_count')" />
                @elseif ($type === 'drop_in')
                    <x-input wire:model="validity_days" type="number" label="Validity (days)" placeholder="30" :error="$errors->first('validity_days')" />
                @elseif ($type === 'private')
                    <x-input wire:model="session_count" type="number" label="Session Count" placeholder="Leave blank for unlimited" :error="$errors->first('session_count')" />
                @endif

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Description</label>
                    <textarea wire:model="description" rows="2" aria-label="Package description"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="Optional description..."></textarea>
                </div>

                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded accent-navy"> Active
                    </label>
                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                        <input type="checkbox" wire:model="is_popular" class="rounded accent-navy"> Mark as Popular
                    </label>
                </div>
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
