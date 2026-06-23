<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Packages</h2>
        <p class="text-sm text-muted">Manage pricing packages per location.</p>
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

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
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
        @if ($totalCount > 0)
            <p class="text-xs text-muted mt-3">
                <span class="font-semibold text-ink">{{ $totalCount }}</span>
                package{{ $totalCount === 1 ? '' : 's' }}
                across <span class="font-semibold text-ink">{{ $locationCount }}</span>
                location{{ $locationCount === 1 ? '' : 's' }}.
            </p>
        @endif
    </x-card>

    @php
        $typeMeta = [
            'registration' => ['label' => 'Registration', 'class' => 'bg-[#1D4ED8]/10 text-[#1D4ED8]', 'dot' => 'bg-[#1D4ED8]'],
            'regular'      => ['label' => 'Regular',      'class' => 'bg-navy/10 text-navy',           'dot' => 'bg-navy'],
            'drop_in'      => ['label' => 'Drop-in',      'class' => 'bg-[#B45309]/10 text-[#B45309]', 'dot' => 'bg-[#B45309]'],
            'private'      => ['label' => 'Private',      'class' => 'bg-[#7C3AED]/10 text-[#7C3AED]', 'dot' => 'bg-[#7C3AED]'],
        ];
    @endphp

    {{-- Grouped by location --}}
    @forelse ($groups as $group)
        @php
            $location    = $group->first()->location;
            $activeCount = $group->where('is_active', true)->count();
        @endphp

        <x-card padding="p-0" class="mb-4 overflow-hidden"
                x-data="{ expanded: {{ ($search || $filterLocation) ? 'true' : 'false' }} }">
            {{-- Location header (toggle) --}}
            <button type="button" x-on:click="expanded = !expanded"
                    class="w-full flex items-center gap-3 px-4 sm:px-5 py-3.5 text-left transition-colors hover:bg-navy/[0.05]"
                    :class="expanded ? 'bg-navy/[0.04]' : 'bg-navy/[0.02]'">
                <div class="w-9 h-9 rounded-xl bg-navy/8 text-navy flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="font-extrabold text-navy text-sm sm:text-base truncate leading-tight">{{ $location?->name ?? 'Unassigned' }}</p>
                    @if ($location?->address)
                        <p class="text-[11px] text-muted truncate">{{ $location->address }}</p>
                    @endif
                </div>
                <span class="shrink-0 text-[11px] font-semibold text-muted tabular-nums">
                    {{ $group->count() }} {{ $group->count() === 1 ? 'package' : 'packages' }}
                    <span class="text-faint hidden sm:inline">· {{ $activeCount }} active</span>
                </span>
                <svg class="w-4 h-4 text-muted shrink-0 transition-transform duration-200"
                     :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Packages (collapsible) --}}
            <div x-show="expanded" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="overflow-x-auto border-t border-line">
                <table class="w-full text-sm min-w-[680px]">
                    <tbody class="divide-y divide-line">
                        @foreach ($group as $package)
                            @php $meta = $typeMeta[$package->type] ?? ['label' => str_replace('_', ' ', $package->type), 'class' => 'bg-navy/5 text-muted', 'dot' => 'bg-muted']; @endphp
                            <tr class="hover:bg-off transition-colors {{ $package->is_active ? '' : 'opacity-60' }}">
                                {{-- Name + meta --}}
                                <td class="py-3 pl-4 sm:pl-5 pr-4">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $meta['dot'] }}"></span>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-ink truncate">{{ $package->name }}</span>
                                                @if ($package->is_popular)
                                                    <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-[#B45309] uppercase tracking-wide shrink-0">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                        Popular
                                                    </span>
                                                @endif
                                            </div>
                                            @php
                                                $bits = [];
                                                if ($package->session_count) $bits[] = $package->session_count . ' session' . ($package->session_count === 1 ? '' : 's');
                                                if ($package->validity_days) $bits[] = $package->validity_days . '-day validity';
                                            @endphp
                                            @if ($bits)
                                                <p class="text-[11px] text-muted truncate">{{ implode(' · ', $bits) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                {{-- Type --}}
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $meta['class'] }}">
                                        {{ $meta['label'] }}
                                    </span>
                                </td>
                                {{-- Price --}}
                                <td class="py-3 px-4 text-right">
                                    <span class="font-bold text-ink tabular-nums whitespace-nowrap">{{ $package->formattedPrice() }}</span>
                                </td>
                                {{-- Status --}}
                                <td class="py-3 px-4">
                                    <x-badge :status="$package->is_active ? 'active' : 'inactive'">
                                        {{ $package->is_active ? 'Active' : 'Inactive' }}
                                    </x-badge>
                                </td>
                                {{-- Actions --}}
                                <td class="py-3 pr-4 sm:pr-5 pl-4">
                                    <div class="flex items-center gap-2 justify-end">
                                        <x-btn variant="edit" size="sm" wire:click="openEdit({{ $package->id }})" wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            Edit
                                        </x-btn>
                                        <x-btn variant="{{ $package->is_active ? 'warning' : 'success' }}" size="sm"
                                               wire:click="toggleActive({{ $package->id }})" wire:loading.attr="disabled">
                                            @if ($package->is_active)
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                Deactivate
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                                Activate
                                            @endif
                                        </x-btn>
                                        <x-btn variant="danger" size="sm"
                                               wire:click="confirmDelete({{ $package->id }})"
                                               wire:confirm="Delete this package?" wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Delete
                                        </x-btn>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @empty
        <x-card padding="p-0">
            <x-empty-state
                title="{{ $search || $filterLocation ? 'No packages match your filters' : 'No packages yet' }}"
                description="{{ $search || $filterLocation ? 'Try a different search or location.' : 'Add your first pricing package.' }}" />
        </x-card>
    @endforelse

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
                    <x-input wire:model="validity_days" type="number" label="Validity (Days)" placeholder="e.g. 30"
                             required :error="$errors->first('validity_days')"
                             helper="How many days the package is valid from the parent's chosen start date." />
                    <x-input wire:model="session_count" type="number" label="Session Count" placeholder="e.g. 8" :error="$errors->first('session_count')" />
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
