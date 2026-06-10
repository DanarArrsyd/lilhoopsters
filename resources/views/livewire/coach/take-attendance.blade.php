<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Take Attendance</h2>
        <p class="text-sm text-muted">Mark attendance for your sessions.</p>
    </div>

    {{-- Flash --}}
    @if (session('attendance_success'))
        <x-alert type="success" class="mb-4">{{ session('attendance_success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-select wire:model.live="scheduleId" label="Schedule">
                <option value="">— Select a schedule —</option>
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
            <x-empty-state title="No enrolled students for this schedule" description="Students appear once approved." />
        @else
            <x-card padding="p-0">
                <div class="px-4 py-3 border-b border-line">
                    <h3 class="text-sm font-bold uppercase tracking-wide text-navy">
                        Roster ({{ count($roster) }} students)
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
                                    'present' => ['label' => 'Present', 'active' => 'bg-[#15803D] text-white border-[#15803D]'],
                                    'no_show' => ['label' => 'Absent',  'active' => 'bg-[#B91C1C] text-white border-[#B91C1C]'],
                                    'sick'    => ['label' => 'Sick',    'active' => 'bg-[#1D4ED8] text-white border-[#1D4ED8]'],
                                    'permit'  => ['label' => 'Permit',  'active' => 'bg-[#B45309] text-white border-[#B45309]'],
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
                    <x-btn wire:click="saveAttendance" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveAttendance">Save Attendance</span>
                        <span wire:loading wire:target="saveAttendance">Saving...</span>
                    </x-btn>
                </div>
            </x-card>
        @endif
    @endif
</div>
