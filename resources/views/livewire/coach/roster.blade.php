<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Daily Roster</h2>
        <p class="text-sm text-muted">Attendance summary per session.</p>
    </div>

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-select wire:model.live="scheduleId" label="Schedule">
                <option value="">— Select schedule —</option>
                @foreach ($schedules as $s)
                    <option value="{{ $s->id }}">
                        {{ ucfirst($s->day_of_week) }} · {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}
                        · {{ $s->program->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input type="date" wire:model.live="date" label="Date" />
        </div>
    </x-card>

    @if ($scheduleId)
        {{-- Stats --}}
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3 mb-4">
            @foreach ([
                ['label' => 'Total',        'value' => $stats['total'],                           'color' => 'text-navy'],
                ['label' => 'Present',      'value' => $stats['present'],                         'color' => 'text-[#15803D]'],
                ['label' => 'Absent',       'value' => $stats['absent'],                          'color' => 'text-[#B91C1C]'],
                ['label' => 'Sick/Permit',  'value' => $stats['sick'] + $stats['permit'],         'color' => 'text-[#1D4ED8]'],
                ['label' => 'Not Recorded', 'value' => $stats['not_recorded'],                    'color' => 'text-faint'],
            ] as $s)
                <x-card padding="p-3">
                    <p class="text-xl font-extrabold {{ $s['color'] }} text-center">{{ $s['value'] }}</p>
                    <p class="text-xs text-muted mt-0.5 text-center">{{ $s['label'] }}</p>
                </x-card>
            @endforeach
        </div>

        {{-- Roster table --}}
        <x-card padding="p-0">
            @if ($roster->isEmpty())
                <x-empty-state title="No enrolled students for this schedule" description="Students appear once they are approved." />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[400px]">
                        <thead>
                            <tr class="border-b border-line">
                                <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Student</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Jersey</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($roster as $row)
                                <tr class="hover:bg-off transition-colors">
                                    <td class="py-3 px-4 font-semibold text-ink">{{ $row['name'] }}</td>
                                    <td class="py-3 px-4 text-muted">{{ $row['jersey'] ?? '—' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($row['status'])
                                            <x-badge :status="$row['status']">{{ ucfirst(str_replace('_', ' ', $row['status'])) }}</x-badge>
                                        @else
                                            <span class="text-xs text-faint">Not recorded</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </x-card>
    @endif
</div>
