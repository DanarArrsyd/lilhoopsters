<div>
    @if ($showModal)

        {{-- ═══ INLINE WIZARD ═══ --}}
        <div class="max-w-2xl mx-auto">

            {{-- Back link --}}
            <button wire:click="$set('showModal', false)"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.admin.schedules.title') }}
            </button>

            {{-- Wizard card --}}
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">

                {{-- Header + progress --}}
                <div class="px-6 pt-6 pb-5 border-b border-line">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy">
                                {{ $editingId ? __('messages.admin.schedules.modal_edit') : __('messages.admin.schedules.modal_new') }}
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                {{ __('messages.admin.schedules.step_setup') }} &amp; {{ __('messages.admin.schedules.step_timing') }}
                            </p>
                        </div>
                        <span class="text-sm font-bold text-faint">{{ $step }}/2</span>
                    </div>

                    {{-- Progress bar --}}
                    <div class="h-1.5 bg-line rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-navy rounded-full transition-all duration-300"
                             style="width: {{ $step / 2 * 100 }}%"></div>
                    </div>

                    {{-- Step dots --}}
                    <div class="flex items-center">
                        @foreach ([1 => __('messages.admin.schedules.step_setup'), 2 => __('messages.admin.schedules.step_timing')] as $n => $label)
                            <div class="flex items-center gap-2">
                                <div @class([
                                    'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-colors',
                                    'bg-[#15803D] text-white' => $step > $n,
                                    'bg-navy text-off'        => $step === $n,
                                    'bg-line text-faint'      => $step < $n,
                                ])>
                                    @if ($step > $n)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $n }}
                                    @endif
                                </div>
                                <span @class(['text-sm font-semibold transition-colors', 'text-navy' => $step === $n, 'text-faint' => $step !== $n])>
                                    {{ $label }}
                                </span>
                            </div>
                            @if ($n < 2)
                                <div class="flex-1 h-px bg-line mx-3"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Form content --}}
                <div class="px-6 py-6">

                    {{-- ── Step 1: Location + Type + Program/Coach ── --}}
                    @if ($step === 1)
                        <div class="space-y-5">

                            <x-select wire:model="location_id"
                                      label="{{ __('messages.admin.schedules.label_location') }}"
                                      :error="$errors->first('location_id')">
                                <option value="">{{ __('messages.admin.schedules.select_location') }}</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </x-select>

                            {{-- Schedule type picker --}}
                            <div>
                                <p class="block text-xs font-bold uppercase tracking-wider text-muted mb-2">
                                    {{ __('messages.admin.schedules.label_type') }}
                                </p>
                                <div class="grid grid-cols-2 gap-2">
                                    <button type="button" wire:click="$set('type', 'regular')"
                                            @class([
                                                'text-left border-2 rounded-xl px-3 py-2.5 transition-all duration-150',
                                                'border-[#1D4ED8] bg-blue-50' => $type === 'regular',
                                                'border-line bg-surface hover:border-navy/30' => $type !== 'regular',
                                            ])>
                                        <p @class(['text-xs font-extrabold', 'text-[#1D4ED8]' => $type === 'regular', 'text-ink' => $type !== 'regular'])>
                                            {{ __('messages.admin.schedules.type_regular') }}
                                        </p>
                                        <p @class(['text-[10px] mt-0.5', 'text-[#3B82F6]' => $type === 'regular', 'text-faint' => $type !== 'regular'])>
                                            {{ __('messages.admin.schedules.regular_desc') }}
                                        </p>
                                    </button>
                                    <button type="button" wire:click="$set('type', 'private')"
                                            @class([
                                                'text-left border-2 rounded-xl px-3 py-2.5 transition-all duration-150',
                                                'border-[#7C3AED] bg-purple-50' => $type === 'private',
                                                'border-line bg-surface hover:border-navy/30' => $type !== 'private',
                                            ])>
                                        <p @class(['text-xs font-extrabold', 'text-[#7C3AED]' => $type === 'private', 'text-ink' => $type !== 'private'])>
                                            {{ __('messages.admin.schedules.type_private') }}
                                        </p>
                                        <p @class(['text-[10px] mt-0.5', 'text-[#8B5CF6]' => $type === 'private', 'text-faint' => $type !== 'private'])>
                                            {{ __('messages.admin.schedules.private_desc') }}
                                        </p>
                                    </button>
                                </div>
                                @error('type') <p class="text-xs text-[#B91C1C] mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Program: only for Regular --}}
                            @if ($type !== 'private')
                                <x-select wire:model="program_id"
                                          label="{{ __('messages.admin.schedules.label_program') }}"
                                          :error="$errors->first('program_id')">
                                    <option value="">{{ __('messages.admin.schedules.select_program') }}</option>
                                    @foreach ($programs as $prog)
                                        <option value="{{ $prog->id }}">{{ $prog->name }} ({{ round($prog->min_age_months/12) }}–{{ round($prog->max_age_months/12) }}yr)</option>
                                    @endforeach
                                </x-select>
                            @endif

                            {{-- Coach: only for Private --}}
                            @if ($type === 'private')
                                <x-select wire:model="coach_id"
                                          label="{{ __('messages.admin.schedules.label_coach') }}"
                                          required :error="$errors->first('coach_id')">
                                    <option value="">{{ __('messages.admin.schedules.select_coach') }}</option>
                                    @foreach ($coaches as $coach)
                                        <option value="{{ $coach->id }}">{{ $coach->user->name }}</option>
                                    @endforeach
                                </x-select>
                            @endif

                        </div>

                        <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-line">
                            <button wire:click="$set('showModal', false)"
                                    class="text-sm font-semibold text-muted hover:text-navy transition-colors px-4 py-2">
                                {{ __('messages.common.cancel') }}
                            </button>
                            <button wire:click="nextStep" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 bg-navy text-off text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="nextStep">{{ __('messages.common.next') }}</span>
                                <span wire:loading wire:target="nextStep">...</span>
                                <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>

                    {{-- ── Step 2: Day + Time + Capacity + Active ── --}}
                    @elseif ($step === 2)
                        <div class="space-y-5">

                            <x-select wire:model="day_of_week"
                                      label="{{ __('messages.admin.schedules.label_day') }}"
                                      :error="$errors->first('day_of_week')">
                                <option value="">{{ __('messages.admin.schedules.select_day') }}</option>
                                @foreach ($days as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </x-select>

                            {{-- Start / End time pickers --}}
                            <div class="grid grid-cols-2 gap-4">
                                {{-- Start Time --}}
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                                        {{ __('messages.admin.schedules.label_start') }} <span class="text-[#DC2626] ml-0.5">*</span>
                                    </label>
                                    <div class="flex items-center gap-1 rounded-xl border bg-surface px-2 py-2 {{ $errors->first('start_time') ? 'border-[#DC2626]' : 'border-line' }}">
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
                                        {{ __('messages.admin.schedules.label_end') }} <span class="text-[#DC2626] ml-0.5">*</span>
                                    </label>
                                    <div class="flex items-center gap-1 rounded-xl border bg-surface px-2 py-2 {{ $errors->first('end_time') ? 'border-[#DC2626]' : 'border-line' }}">
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

                            <x-input wire:model="max_capacity" type="number"
                                     label="{{ __('messages.admin.schedules.label_capacity') }}"
                                     placeholder="20" required :error="$errors->first('max_capacity')" />

                            <div class="flex items-center justify-between px-5 py-4 bg-off rounded-xl border border-line">
                                <p class="text-sm font-semibold text-ink">{{ __('messages.admin.schedules.label_active') }}</p>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                    <div class="w-10 h-6 bg-line peer-focus:outline-none rounded-full peer
                                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:bg-navy"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 mt-6 pt-5 border-t border-line">
                            <button wire:click="back"
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-navy transition-colors px-4 py-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                {{ __('messages.common.back') }}
                            </button>
                            <button wire:click="save" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 bg-navy text-off text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all disabled:opacity-50">
                                <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span wire:loading.remove wire:target="save">
                                    {{ $editingId ? __('messages.common.update') : __('messages.common.save') }}
                                </span>
                                <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    @else

        {{-- ═══ LIST VIEW ═══ --}}
        <div class="max-w-6xl mx-auto">

        <x-admin.page-header :title="__('messages.admin.schedules.title')" :subtitle="__('messages.admin.schedules.subtitle')">
            <x-slot name="action">
                <x-btn variant="add" wire:click="openCreate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('messages.admin.schedules.new') }}
                </x-btn>
            </x-slot>
        </x-admin.page-header>

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        {{-- Type tabs --}}
        <div class="flex gap-1 mb-4">
            @foreach (['' => __('messages.admin.schedules.type_all'), 'regular' => __('messages.admin.schedules.type_regular'), 'private' => __('messages.admin.schedules.type_private')] as $val => $label)
                <button wire:click="$set('filterType', '{{ $val }}')"
                        @class([
                            'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-wide transition-colors',
                            'bg-navy text-white'              => $filterType === $val,
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

                <div x-show="expanded" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="overflow-x-auto border-t border-line">
                    <table class="w-full text-sm min-w-[720px]">
                        <thead>
                            <tr class="border-b border-line bg-off/50">
                                <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.schedules.col_program') }}</th>
                                <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.schedules.col_day_time') }}</th>
                                <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.schedules.col_type') }}</th>
                                <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.schedules.col_coach') }}</th>
                                <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.schedules.col_capacity') }}</th>
                                <th class="text-left py-2.5 px-4 text-[11px] font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.schedules.col_status') }}</th>
                                <th class="py-2.5 px-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($group as $schedule)
                                @php $isPrivate = $schedule->type === 'private'; @endphp
                                <tr class="hover:bg-off transition-colors {{ $schedule->is_active ? '' : 'opacity-60' }}">
                                    <td class="py-3 px-4">
                                        @if ($isPrivate)
                                            <span class="text-faint text-xs italic">—</span>
                                        @else
                                            <div class="flex items-center gap-2.5">
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0 bg-[#1D4ED8]"></span>
                                                <span class="font-semibold text-ink">{{ $schedule->program->name }}</span>
                                            </div>
                                        @endif
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
                                            {{ $isPrivate ? __('messages.admin.schedules.type_private') : __('messages.admin.schedules.type_regular') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-muted">
                                        @if ($isPrivate)
                                            {{ $schedule->coach?->user->name ?? '—' }}
                                        @else
                                            <span class="text-faint italic text-xs">{{ __('messages.admin.schedules.any_coach') }}</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 tabular-nums">
                                        <span class="font-semibold text-ink">{{ $schedule->approvedEnrollmentsCount() }}</span>
                                        <span class="text-muted">/ {{ $schedule->max_capacity }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-badge :status="$schedule->is_active ? 'active' : 'inactive'">
                                            {{ $schedule->is_active ? __('messages.status.active') : __('messages.status.inactive') }}
                                        </x-badge>
                                    </td>
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2 justify-end">
                                            <x-btn variant="edit" size="sm" wire:click="openEdit({{ $schedule->id }})" wire:loading.attr="disabled">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                {{ __('messages.common.edit') }}
                                            </x-btn>
                                            <x-btn variant="{{ $schedule->is_active ? 'warning' : 'success' }}" size="sm"
                                                   wire:click="toggleActive({{ $schedule->id }})" wire:loading.attr="disabled">
                                                @if ($schedule->is_active)
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                    {{ __('messages.common.deactivate') }}
                                                @else
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                                    {{ __('messages.common.activate') }}
                                                @endif
                                            </x-btn>
                                            <x-btn variant="danger" size="sm"
                                                   wire:click="confirmDelete({{ $schedule->id }})"
                                                   wire:confirm="Delete this schedule?" wire:loading.attr="disabled">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                {{ __('messages.common.delete') }}
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
                    :title="$search || $filterLocation || $filterType ? __('messages.admin.schedules.empty_no_match') : __('messages.admin.schedules.empty_title')"
                    :description="$search || $filterLocation || $filterType ? __('messages.admin.schedules.empty_nm_desc') : __('messages.admin.schedules.empty_desc')" />
            </x-card>
        @endforelse

        </div>{{-- /max-w-6xl --}}

    @endif
</div>
