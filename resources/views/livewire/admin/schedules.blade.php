<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Schedules</h2>
            <p class="text-sm text-muted">Manage class schedules per location and program.</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Schedule</x-btn>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by location or program..." />
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
                        <th class="text-left py-3 px-4 font-bold text-muted">Location</th>
                        <th class="text-left py-3 px-4 font-bold text-muted">Program</th>
                        <th class="text-left py-3 px-4 font-bold text-muted">Day &amp; Time</th>
                        <th class="text-left py-3 px-4 font-bold text-muted">Coach</th>
                        <th class="text-left py-3 px-4 font-bold text-muted">Capacity</th>
                        <th class="text-left py-3 px-4 font-bold text-muted">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($schedules as $schedule)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $schedule->location->name }}</td>
                            <td class="py-3 px-4 text-ink">{{ $schedule->program->name }}</td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink capitalize">{{ $schedule->day_of_week }}</p>
                                <p class="text-xs text-faint">
                                    {{ substr($schedule->start_time, 0, 5) }} – {{ substr($schedule->end_time, 0, 5) }}
                                </p>
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ $schedule->coach?->user->name ?? '—' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-semibold text-ink">{{ $schedule->approvedEnrollmentsCount() }}</span>
                                <span class="text-muted">/ {{ $schedule->max_capacity }}</span>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$schedule->is_active ? 'active' : 'inactive'">
                                    {{ $schedule->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="ghost" size="sm"
                                           wire:click="openEdit({{ $schedule->id }})"
                                           wire:loading.attr="disabled">Edit</x-btn>
                                    <x-btn variant="ghost" size="sm"
                                           wire:click="toggleActive({{ $schedule->id }})"
                                           wire:loading.attr="disabled">
                                        {{ $schedule->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-btn>
                                    <x-btn variant="ghost" size="sm"
                                           wire:click="confirmDelete({{ $schedule->id }})"
                                           wire:confirm="Delete this schedule?"
                                           wire:loading.attr="disabled">Delete</x-btn>
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
        </div>
        @if ($schedules->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $schedules->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? 'Edit Schedule' : 'New Schedule' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                {{-- Location select --}}
                <x-select wire:model="location_id" label="Location" :error="$errors->first('location_id')">
                    <option value="">Select location...</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>

                {{-- Program select --}}
                <x-select wire:model="program_id" label="Program" :error="$errors->first('program_id')">
                    <option value="">Select program...</option>
                    @foreach ($programs as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }} ({{ $prog->min_age_months }}–{{ $prog->max_age_months }}mo)</option>
                    @endforeach
                </x-select>

                {{-- Coach select (optional) --}}
                <x-select wire:model="coach_id" label="Coach">
                    <option value="">Unassigned</option>
                    @foreach ($coaches as $coach)
                        <option value="{{ $coach->id }}">{{ $coach->user->name }}</option>
                    @endforeach
                </x-select>

                {{-- Day select --}}
                <x-select wire:model="day_of_week" label="Day" :error="$errors->first('day_of_week')">
                    <option value="">Select day...</option>
                    @foreach ($days as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </x-select>

                {{-- Start/End time --}}
                <div class="grid grid-cols-2 gap-4">
                    <x-input wire:model="start_time" type="time" label="Start Time" required :error="$errors->first('start_time')" />
                    <x-input wire:model="end_time" type="time" label="End Time" required :error="$errors->first('end_time')" />
                </div>

                <x-input wire:model="max_capacity" type="number" label="Max Capacity" placeholder="20" required :error="$errors->first('max_capacity')" />

                <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="rounded accent-navy"> Active
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
