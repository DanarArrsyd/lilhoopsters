<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Schedules</h2>
            <p class="text-sm text-slate-500">Manage class schedules per location and program</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Schedule</x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by location or program..." />
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
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Location</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Program</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Day & Time</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Coach</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Capacity</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($schedules as $schedule)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $schedule->location->name }}</td>
                        <td class="py-3 px-4 text-slate-700">{{ $schedule->program->name }}</td>
                        <td class="py-3 px-4">
                            <p class="font-medium text-slate-900 capitalize">{{ $schedule->day_of_week }}</p>
                            <p class="text-xs text-slate-400">
                                {{ substr($schedule->start_time, 0, 5) }} – {{ substr($schedule->end_time, 0, 5) }}
                            </p>
                        </td>
                        <td class="py-3 px-4 text-slate-500">
                            {{ $schedule->coach?->user->name ?? '—' }}
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-slate-700 font-medium">{{ $schedule->approvedEnrollmentsCount() }}</span>
                            <span class="text-slate-400">/ {{ $schedule->max_capacity }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <x-badge :status="$schedule->is_active ? 'active' : 'inactive'">
                                {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2 justify-end">
                                <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $schedule->id }})">Edit</x-btn>
                                <x-btn variant="ghost" size="sm" wire:click="toggleActive({{ $schedule->id }})">
                                    {{ $schedule->is_active ? 'Deactivate' : 'Activate' }}
                                </x-btn>
                                <x-btn variant="ghost" size="sm"
                                       wire:click="confirmDelete({{ $schedule->id }})"
                                       wire:confirm="Delete this schedule?">Delete</x-btn>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-2">
                            <x-empty-state title="No schedules yet" description="Add your first class schedule." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($schedules->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $schedules->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 sticky top-0 bg-white">
                    <h3 class="font-semibold text-slate-900">{{ $editingId ? 'Edit Schedule' : 'Add Schedule' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <div class="p-6 space-y-4">
                    {{-- Location --}}
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

                    {{-- Program --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Program <span class="text-red-500">*</span></label>
                        <select wire:model="program_id"
                                class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 {{ $errors->has('program_id') ? 'border-red-400' : '' }}">
                            <option value="">Select program...</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}">{{ $prog->name }} ({{ $prog->min_age_months }}–{{ $prog->max_age_months }}mo)</option>
                            @endforeach
                        </select>
                        @error('program_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Coach (optional) --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Coach <span class="text-xs text-slate-400">(optional)</span></label>
                        <select wire:model="coach_id"
                                class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                            <option value="">Unassigned</option>
                            @foreach ($coaches as $coach)
                                <option value="{{ $coach->id }}">{{ $coach->user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Day --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Day <span class="text-red-500">*</span></label>
                        <select wire:model="day_of_week"
                                class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500 {{ $errors->has('day_of_week') ? 'border-red-400' : '' }}">
                            <option value="">Select day...</option>
                            @foreach ($days as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('day_of_week') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Time --}}
                    <div class="grid grid-cols-2 gap-4">
                        <x-input wire:model="start_time" type="time" label="Start Time"
                                 required :error="$errors->first('start_time')" />
                        <x-input wire:model="end_time" type="time" label="End Time"
                                 required :error="$errors->first('end_time')" />
                    </div>

                    <x-input wire:model="max_capacity" type="number" label="Max Capacity"
                             placeholder="20" required :error="$errors->first('max_capacity')" />

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
