<div class="max-w-6xl mx-auto">
    <x-admin.page-header :title="__('messages.coach.take_attendance.title')" :subtitle="__('messages.coach.take_attendance.subtitle')" />

    {{-- Flash --}}
    @if (session('attendance_success'))
        <x-alert type="success" class="mb-4">{{ session('attendance_success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-select wire:model.live="scheduleId" label="Schedule">
                <option value="">{{ __('messages.coach.take_attendance.select_schedule') }}</option>
                @foreach ($schedules as $s)
                    <option value="{{ $s->id }}">
                        {{ ucfirst($s->day_of_week) }} · {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}
                        – {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}
                        · {{ $s->program->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input type="date" wire:model.live="date" label="Date" />
        </div>
    </x-card>

    {{-- Roster --}}
    @if ($scheduleId)
        @if (empty($roster))
            <x-empty-state :title="__('messages.coach.take_attendance.empty_title')" :description="__('messages.coach.take_attendance.empty_desc')" />
        @else
            <x-card padding="p-0">
                <div class="px-4 py-3 border-b border-line">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-navy">
                        {{ __('messages.coach.take_attendance.roster_header', ['n' => count($roster)]) }}
                    </h3>
                </div>

                <div class="divide-y divide-line">
                    @foreach ($roster as $row)
                        <div class="px-4 py-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-navy/8 rounded-full flex items-center justify-center text-navy font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($row['name'], 0, 1)) }}
                                </div>
                                <p class="text-sm font-semibold text-ink">{{ $row['name'] }}</p>
                            </div>

                            <div class="flex items-center gap-2 flex-wrap justify-end">
                                @foreach ([
                                    'present' => ['label' => __('messages.coach.take_attendance.present'), 'active' => 'bg-[#15803D] text-white border-[#15803D]'],
                                    'no_show' => ['label' => __('messages.coach.take_attendance.no_show'), 'active' => 'bg-[#B91C1C] text-white border-[#B91C1C]'],
                                    'sick'    => ['label' => __('messages.coach.take_attendance.sick'),    'active' => 'bg-[#1D4ED8] text-white border-[#1D4ED8]'],
                                    'permit'  => ['label' => __('messages.coach.take_attendance.permit'),  'active' => 'bg-[#B45309] text-white border-[#B45309]'],
                                ] as $val => $cfg)
                                    <button type="button"
                                        wire:click="setStatus({{ $row['child_id'] }}, '{{ $val }}')"
                                        class="text-xs px-3 py-1.5 rounded-xl font-semibold border transition-colors
                                            {{ ($row['status'] ?? '') === $val
                                                ? $cfg['active']
                                                : 'bg-surface text-ink border-line hover:bg-off' }}">
                                        {{ $cfg['label'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-4 py-4 border-t border-line">
                    <x-btn variant="success" wire:click="saveAttendance" wire:loading.attr="disabled">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span wire:loading.remove wire:target="saveAttendance">{{ __('messages.coach.take_attendance.save') }}</span>
                        <span wire:loading wire:target="saveAttendance">{{ __('messages.common.saving') }}</span>
                    </x-btn>
                </div>
            </x-card>
        @endif
    @endif
</div>
