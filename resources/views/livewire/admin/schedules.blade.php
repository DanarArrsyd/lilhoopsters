<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Schedules</h2>
        <p class="text-sm text-muted">Manage class schedules per location and program.</p>
    </div>
    <button wire:click="openCreate"
            class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Type tabs --}}
    <div class="flex gap-1 mb-4">
        @foreach (['' => 'All', 'regular' => 'Regular', 'private' => 'Private'] as $val => $label)
            <button wire:click="$set('filterType', '{{ $val }}')"
                    @class([
                        'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide transition-colors',
                        'bg-navy text-white'          => $filterType === $val,
                        'bg-off text-muted hover:bg-line' => $filterType !== $val,
                    ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by location or program..." />
            </div>
            <div class="w-full sm:w-56">
                <x-select wire:model.live="filterLocation">
                    <option value="">{{ __('messages.admin.schedules.all_locations') }}</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>
        @if ($totalCount > 0)
            <p class="text-xs text-muted mt-3">
                <span class="font-semibold text-ink">{{ $totalCount }}</span>
                schedule{{ $totalCount === 1 ? '' : 's' }}
                across <span class="font-semibold text-ink">{{ $locationCount }}</span>
                location{{ $locationCount === 1 ? '' : 's' }}.
            </p>
        @endif
    </x-card>

    {{-- Grouped by location --}}
    @forelse ($groups as $group)
        @php
            $location    = $group->first()->location;
            $activeCount = $group->where('is_active', true)->count();
            $autoOpen    = ($search || $filterLocation || $filterType) ? 'true' : 'false';
        @endphp

        <x-card padding="p-0" class="mb-4 overflow-hidden"
                x-data="{ expanded: {{ $autoOpen }} }">
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
                    {{ $group->count() }} {{ $group->count() === 1 ? 'schedule' : 'schedules' }}
                    <span class="text-faint hidden sm:inline">· {{ $activeCount }} active</span>
                </span>
                <svg class="w-4 h-4 text-muted shrink-0 transition-transform duration-200"
                     :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Schedules (collapsible) --}}
            <div x-show="expanded" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 -translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 class="overflow-x-auto border-t border-line">
                <table class="w-full text-sm min-w-[720px]">
                    <thead>
                        <tr class="border-b border-line bg-off/50">
                            <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">Program</th>
                            <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">Day &amp; Time</th>
                            <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">Type</th>
                            <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">Coach</th>
                            <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">Capacity</th>
                            <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">Status</th>
                            <th class="py-2.5 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($group as $schedule)
                            @php $isPrivate = $schedule->type === 'private'; @endphp
                            <tr class="hover:bg-off transition-colors {{ $schedule->is_active ? '' : 'opacity-60' }}">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $isPrivate ? 'bg-[#7C3AED]' : 'bg-[#1D4ED8]' }}"></span>
                                        <span class="font-semibold text-ink">{{ $schedule->program->name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <p class="font-semibold text-ink capitalize">{{ $schedule->day_of_week }}</p>
                                    <p class="text-xs text-faint tabular-nums">
                                        {{ \Carbon\Carbon::createFromTimeString($schedule->start_time)->format('g:i A') }}
                                        –
                                        {{ \Carbon\Carbon::createFromTimeString($schedule->end_time)->format('g:i A') }}
                                    </p>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide
                                        {{ $isPrivate ? 'bg-[#7C3AED]/10 text-[#7C3AED]' : 'bg-[#1D4ED8]/10 text-[#1D4ED8]' }}">
                                        {{ $isPrivate ? 'Private' : 'Regular' }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-muted">
                                    @if ($isPrivate)
                                        {{ $schedule->coach?->user->name ?? '—' }}
                                    @else
                                        <span class="text-faint italic text-xs">Any coach</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 tabular-nums">
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
                                        <x-btn variant="edit" size="sm"
                                               wire:click="openEdit({{ $schedule->id }})"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            Edit
                                        </x-btn>
                                        <x-btn variant="{{ $schedule->is_active ? 'warning' : 'success' }}" size="sm"
                                               wire:click="toggleActive({{ $schedule->id }})"
                                               wire:loading.attr="disabled">
                                            @if ($schedule->is_active)
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                Deactivate
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                                Activate
                                            @endif
                                        </x-btn>
                                        <x-btn variant="danger" size="sm"
                                               wire:click="confirmDelete({{ $schedule->id }})"
                                               wire:confirm="Delete this schedule?"
                                               wire:loading.attr="disabled">
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
                title="{{ $search || $filterLocation || $filterType ? 'No schedules match your filters' : 'No schedules yet' }}"
                description="{{ $search || $filterLocation || $filterType ? 'Try a different search, type, or location.' : 'Add your first class schedule.' }}" />
        </x-card>
    @endforelse

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
                    <option value="">{{ __('messages.admin.schedules.select_location') }}</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>

                {{-- Program select --}}
                <x-select wire:model="program_id" label="Program" :error="$errors->first('program_id')">
                    <option value="">{{ __('messages.admin.schedules.select_program') }}</option>
                    @foreach ($programs as $prog)
                        <option value="{{ $prog->id }}">{{ $prog->name }} ({{ $prog->min_age_months }}–{{ $prog->max_age_months }}mo)</option>
                    @endforeach
                </x-select>

                {{-- Schedule type --}}
                <div>
                    <p class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">Schedule Type</p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach (['regular' => ['label' => 'Regular', 'desc' => 'Any coach can take it', 'color' => 'blue'], 'private' => ['label' => 'Private', 'desc' => 'Assign a specific coach', 'color' => 'purple']] as $val => $cfg)
                            <button type="button" wire:click="$set('type', '{{ $val }}')"
                                    class="text-left border-2 rounded-xl px-3 py-2.5 transition-all duration-150
                                        {{ $type === $val
                                            ? ($val === 'private' ? 'border-purple-500 bg-purple-50' : 'border-blue-500 bg-blue-50')
                                            : 'border-line bg-surface hover:border-navy/30' }}">
                                <p class="text-xs font-extrabold {{ $type === $val ? ($val === 'private' ? 'text-purple-700' : 'text-blue-700') : 'text-ink' }}">
                                    {{ $cfg['label'] }}
                                </p>
                                <p class="text-[10px] {{ $type === $val ? ($val === 'private' ? 'text-purple-500' : 'text-blue-500') : 'text-faint' }} mt-0.5">
                                    {{ $cfg['desc'] }}
                                </p>
                            </button>
                        @endforeach
                    </div>
                    @error('type') <p class="text-xs text-[#B91C1C] mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Coach: only for Private --}}
                @if ($type === 'private')
                    <x-select wire:model="coach_id" label="Coach" required :error="$errors->first('coach_id')">
                        <option value="">— Select coach —</option>
                        @foreach ($coaches as $coach)
                            <option value="{{ $coach->id }}">{{ $coach->user->name }}</option>
                        @endforeach
                    </x-select>
                @endif

                {{-- Day select --}}
                <x-select wire:model="day_of_week" label="Day" :error="$errors->first('day_of_week')">
                    <option value="">{{ __('messages.admin.schedules.select_day') }}</option>
                    @foreach ($days as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </x-select>

                {{-- Start/End time — 12h AM/PM picker --}}
                <div class="grid grid-cols-2 gap-4">
                    {{-- Start Time --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                            Start Time <span class="text-[#DC2626] ml-0.5">*</span>
                        </label>
                        <div class="flex items-center gap-1 rounded-xl border bg-surface px-2 py-2
                                    {{ $errors->first('start_time') ? 'border-[#DC2626]' : 'border-line' }}">
                            <select wire:model.live="startHour"
                                    class="flex-1 text-sm font-semibold text-ink bg-transparent border-none outline-none text-center cursor-pointer">
                                @foreach(range(1,12) as $h)
                                    <option value="{{ $h }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                                @endforeach
                            </select>
                            <span class="text-muted font-bold text-sm shrink-0">:</span>
                            <select wire:model.live="startMinute"
                                    class="flex-1 text-sm font-semibold text-ink bg-transparent border-none outline-none text-center cursor-pointer">
                                @foreach(['00','05','10','15','20','25','30','35','40','45','50','55'] as $m)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="toggleStartPeriod"
                                    class="shrink-0 ml-1 w-10 py-1 rounded-lg text-xs font-bold text-white transition-colors duration-150"
                                    style="{{ $startPeriod === 'AM' ? 'background:#0A0F1E' : 'background:#F59E0B' }}">
                                {{ $startPeriod }}
                            </button>
                        </div>
                        @error('start_time') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    {{-- End Time --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                            End Time <span class="text-[#DC2626] ml-0.5">*</span>
                        </label>
                        <div class="flex items-center gap-1 rounded-xl border bg-surface px-2 py-2
                                    {{ $errors->first('end_time') ? 'border-[#DC2626]' : 'border-line' }}">
                            <select wire:model.live="endHour"
                                    class="flex-1 text-sm font-semibold text-ink bg-transparent border-none outline-none text-center cursor-pointer">
                                @foreach(range(1,12) as $h)
                                    <option value="{{ $h }}">{{ str_pad($h, 2, '0', STR_PAD_LEFT) }}</option>
                                @endforeach
                            </select>
                            <span class="text-muted font-bold text-sm shrink-0">:</span>
                            <select wire:model.live="endMinute"
                                    class="flex-1 text-sm font-semibold text-ink bg-transparent border-none outline-none text-center cursor-pointer">
                                @foreach(['00','05','10','15','20','25','30','35','40','45','50','55'] as $m)
                                    <option value="{{ $m }}">{{ $m }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="toggleEndPeriod"
                                    class="shrink-0 ml-1 w-10 py-1 rounded-lg text-xs font-bold text-white transition-colors duration-150"
                                    style="{{ $endPeriod === 'AM' ? 'background:#0A0F1E' : 'background:#F59E0B' }}">
                                {{ $endPeriod }}
                            </button>
                        </div>
                        @error('end_time') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>
                </div>

                <x-input wire:model="max_capacity" type="number" label="Max Capacity" placeholder="20" required :error="$errors->first('max_capacity')" />

                <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                    <input type="checkbox" wire:model="is_active" class="rounded accent-navy"> Active
                </label>
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
