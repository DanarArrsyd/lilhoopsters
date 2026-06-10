<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Programs</h2>
            <p class="text-sm text-slate-500">Manage academy programs and age groups</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Program</x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search programs..." />
    </x-card>

    <x-card>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Program</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Age Range</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Description</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($programs as $program)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $program->name }}</td>
                        <td class="py-3 px-4 text-slate-600 text-xs">
                            <span class="font-mono bg-slate-100 rounded px-1.5 py-0.5">
                                {{ $program->min_age_months }}mo – {{ $program->max_age_months }}mo
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-500 max-w-xs truncate">
                            {{ $program->description ?? '—' }}
                        </td>
                        <td class="py-3 px-4">
                            <x-badge :status="$program->is_active ? 'active' : 'inactive'">
                                {{ $program->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2 justify-end">
                                <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $program->id }})">Edit</x-btn>
                                <x-btn variant="ghost" size="sm" wire:click="toggleActive({{ $program->id }})">
                                    {{ $program->is_active ? 'Deactivate' : 'Activate' }}
                                </x-btn>
                                <x-btn variant="ghost" size="sm"
                                       wire:click="confirmDelete({{ $program->id }})"
                                       wire:confirm="Delete this program?">Delete</x-btn>
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
        @if ($programs->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $programs->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">{{ $editingId ? 'Edit Program' : 'Add Program' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
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
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea wire:model="description" rows="3"
                                  class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                                  placeholder="Optional program description..."></textarea>
                        @error('description') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
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
