<div class="max-w-6xl mx-auto">
    <x-admin.page-header :title="__('messages.coach.roster.title')" :subtitle="__('messages.coach.roster.subtitle')" />

    {{-- Compact filters --}}
    <div class="flex flex-col sm:flex-row gap-2 mb-4">
        <select wire:model.live="scheduleId"
                class="flex-1 rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy appearance-none bg-no-repeat"
                style="background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E\");background-position:right 0.75rem center;background-size:1rem;">
            <option value="">{{ __('messages.coach.roster.select_schedule') }}</option>
            @foreach ($schedules as $s)
                <option value="{{ $s->id }}">
                    {{ ucfirst($s->day_of_week) }} · {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }} · {{ $s->program->name }}
                </option>
            @endforeach
        </select>
        <input type="date" wire:model.live="date"
               class="sm:w-44 rounded-xl border border-line bg-surface px-3.5 py-2.5 text-sm text-ink focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy">
    </div>

    @if ($scheduleId)
        {{-- Compact stats row --}}
        <div class="flex items-center gap-2 mb-3 flex-wrap">
            <span class="text-xs text-faint font-medium">{{ __('messages.coach.roster.students', ['n' => $stats['total']]) }}</span>
            <span class="text-line">·</span>
            <span class="inline-flex items-center gap-1 text-xs font-bold text-[#15803D]">
                <span class="w-1.5 h-1.5 rounded-full bg-[#15803D] inline-block"></span>
                {{ __('messages.coach.roster.present', ['n' => $stats['present']]) }}
            </span>
            @if ($stats['absent'] > 0)
                <span class="text-line">·</span>
                <span class="inline-flex items-center gap-1 text-xs font-bold text-[#B91C1C]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#B91C1C] inline-block"></span>
                    {{ __('messages.coach.roster.no_show', ['n' => $stats['absent']]) }}
                </span>
            @endif
            @if ($stats['sick'] + $stats['permit'] > 0)
                <span class="text-line">·</span>
                <span class="inline-flex items-center gap-1 text-xs font-bold text-[#1D4ED8]">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#1D4ED8] inline-block"></span>
                    {{ __('messages.coach.roster.sick_permit', ['n' => $stats['sick'] + $stats['permit']]) }}
                </span>
            @endif
            @if ($stats['not_recorded'] > 0)
                <span class="text-line">·</span>
                <span class="text-xs text-faint">{{ __('messages.coach.roster.not_recorded', ['n' => $stats['not_recorded']]) }}</span>
            @endif
        </div>

        {{-- Roster list --}}
        @if ($roster->isEmpty())
            <x-empty-state :title="__('messages.coach.roster.empty_title')" :description="__('messages.coach.roster.empty_desc')" />
        @else
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">
                <div class="divide-y divide-line">
                    @foreach ($roster as $row)
                        <div class="flex items-center gap-3 px-4 py-2.5">
                            {{-- Avatar --}}
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0
                                {{ $row['status'] === 'present' ? 'bg-[#15803D] text-white' : 'bg-navy/8 text-navy' }}">
                                {{ strtoupper(substr($row['name'], 0, 1)) }}
                            </div>

                            {{-- Name --}}
                            <span class="flex-1 text-sm font-semibold text-ink truncate">{{ $row['name'] }}</span>

                            {{-- Jersey --}}
                            @if ($row['jersey'])
                                <span class="text-xs text-faint tabular-nums w-8 text-center shrink-0">#{{ $row['jersey'] }}</span>
                            @endif

                            {{-- Status --}}
                            <div class="shrink-0">
                                @if ($row['status'] === 'present')
                                    <span class="text-[10px] font-bold text-[#15803D] bg-[#DCFCE7] px-2 py-0.5 rounded-full">{{ __('messages.coach.roster.badge_present') }}</span>
                                @elseif ($row['status'] === 'no_show')
                                    <span class="text-[10px] font-bold text-[#B91C1C] bg-[#FEE2E2] px-2 py-0.5 rounded-full">{{ __('messages.coach.roster.badge_no_show') }}</span>
                                @elseif ($row['status'] === 'sick')
                                    <span class="text-[10px] font-bold text-[#1D4ED8] bg-blue-50 px-2 py-0.5 rounded-full">{{ __('messages.coach.roster.badge_sick') }}</span>
                                @elseif ($row['status'] === 'permit')
                                    <span class="text-[10px] font-bold text-[#1D4ED8] bg-blue-50 px-2 py-0.5 rounded-full">{{ __('messages.coach.roster.badge_permit') }}</span>
                                @else
                                    <span class="text-[10px] font-medium text-faint">—</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
