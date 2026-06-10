<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Programs</h2>
            <p class="text-sm text-muted">Manage academy programs and age groups.</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Program</x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search programs..." />
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Program</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Age Range</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Description</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($programs as $program)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $program->name }}</td>
                            <td class="py-3 px-4">
                                <span class="font-mono bg-navy/8 text-navy rounded px-1.5 py-0.5 text-xs">
                                    {{ $program->min_age_months }}mo – {{ $program->max_age_months }}mo
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted max-w-xs truncate">
                                {{ $program->description ?? '—' }}
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$program->is_active ? 'active' : 'inactive'">
                                    {{ $program->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $program->id }})"
                                           wire:loading.attr="disabled">Edit</x-btn>
                                    <x-btn variant="ghost" size="sm"
                                           wire:click="toggleActive({{ $program->id }})"
                                           wire:loading.attr="disabled"
                                           wire:target="toggleActive({{ $program->id }})">
                                        {{ $program->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-btn>
                                    <x-btn variant="ghost" size="sm"
                                           wire:click="confirmDelete({{ $program->id }})"
                                           wire:confirm="Delete this program?"
                                           wire:loading.attr="disabled"
                                           wire:target="confirmDelete({{ $program->id }})">Delete</x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-2">
                                <x-empty-state title="No programs yet" description="Add your first academy program." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($programs->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $programs->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? 'Edit Program' : 'New Program' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="name" label="Program Name" placeholder="e.g. Junior, Rookie, MVP"
                             required :error="$errors->first('name')" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-input wire:model="min_age_months" type="number" label="Min Age (months)"
                                 placeholder="18" required :error="$errors->first('min_age_months')"
                                 helper="e.g. 18 = 1.5 years" />
                        <x-input wire:model="max_age_months" type="number" label="Max Age (months)"
                                 placeholder="84" required :error="$errors->first('max_age_months')"
                                 helper="e.g. 84 = 7 years" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Description</label>
                        <textarea wire:model="description" rows="3" aria-label="Program description"
                                  class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                  placeholder="Optional program description..."></textarea>
                        @error('description') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>
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
