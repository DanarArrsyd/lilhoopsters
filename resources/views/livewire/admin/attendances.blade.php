<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Attendances</h2>
        <p class="text-sm text-muted">View and override student attendance records.</p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
        <x-card padding="p-4">
            <p class="text-2xl font-extrabold text-navy">{{ $stats['total'] }}</p>
            <p class="text-xs text-muted mt-1">Total Records</p>
        </x-card>
        <x-card padding="p-4">
            <p class="text-2xl font-extrabold text-[#15803D]">{{ $stats['present'] }}</p>
            <p class="text-xs text-muted mt-1">Present</p>
        </x-card>
        <x-card padding="p-4">
            <p class="text-2xl font-extrabold text-[#B91C1C]">{{ $stats['absent'] }}</p>
            <p class="text-xs text-muted mt-1">Absent</p>
        </x-card>
        <x-card padding="p-4">
            <p class="text-2xl font-extrabold text-[#1D4ED8]">{{ $stats['sick'] }}</p>
            <p class="text-xs text-muted mt-1">Sick / Permit</p>
        </x-card>
    </div>

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Search by child name..." />
            <x-input type="date" wire:model.live="filterDate" />
            <x-select wire:model.live="filterSchedule">
                <option value="">All Schedules</option>
                @foreach ($schedules as $s)
                    <option value="{{ $s->id }}">{{ $s->program->name }} – {{ ucfirst($s->day_of_week) }}</option>
                @endforeach
            </x-select>
            <x-select wire:model.live="filterStatus">
                <option value="">All Statuses</option>
                <option value="present">Present</option>
                <option value="no_show">Absent</option>
                <option value="sick">Sick</option>
                <option value="permit">Permit</option>
                <option value="make_up">Make-Up</option>
            </x-select>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Child</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Schedule</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Date</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Source</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Coach</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($attendances as $a)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $a->child?->name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if ($a->schedule)
                                    <p class="text-ink">{{ $a->schedule->program->name }}</p>
                                    <p class="text-xs text-faint">{{ $a->schedule->location->name }} · {{ ucfirst($a->schedule->day_of_week) }}</p>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-ink">{{ $a->attended_at?->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$a->status">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-muted capitalize">{{ $a->source }}</td>
                            <td class="py-3 px-4 text-muted">{{ $a->coach?->user?->name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                <x-btn variant="ghost" size="sm"
                                       wire:click="openOverride({{ $a->id }})"
                                       wire:loading.attr="disabled">Override</x-btn>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-2">
                                <x-empty-state title="No attendance records found" description="Records appear here once sessions are taken." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendances->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $attendances->links() }}
            </div>
        @endif
    </x-card>

    {{-- Override Modal --}}
    @if ($showOverride)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeOverride"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Override Attendance</h3>
                <button wire:click="closeOverride" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model="overrideStatus" label="Status" :error="$errors->first('overrideStatus')">
                    <option value="present">Present</option>
                    <option value="no_show">Absent</option>
                    <option value="sick">Sick</option>
                    <option value="permit">Permit</option>
                    <option value="make_up">Make-Up</option>
                </x-select>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Notes</label>
                    <textarea wire:model="overrideNotes" rows="3" aria-label="Override notes"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="Optional note..."></textarea>
                    @error('overrideNotes') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeOverride">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="saveOverride" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveOverride">Save Override</span>
                    <span wire:loading wire:target="saveOverride">Saving...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
