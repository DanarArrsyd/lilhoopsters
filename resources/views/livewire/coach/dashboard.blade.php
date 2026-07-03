<div class="max-w-6xl mx-auto space-y-6">

    <x-admin.page-header :title="__('messages.dashboard.title')" :subtitle="__('messages.dashboard.welcome', ['name' => auth()->user()->name])" />

<div class="flex flex-col lg:flex-row gap-6 items-start">

    {{-- LEFT: Weekly Calendar --}}
    <div class="w-full lg:w-68 shrink-0 lg:sticky lg:top-6">
        <x-card padding="p-5">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-extrabold text-navy uppercase tracking-tight">{{ __('messages.coach.dashboard.this_week') }}</p>
                <p class="text-[11px] text-faint font-semibold">{{ now()->format('d M Y') }}</p>
            </div>

            {{-- Day strip --}}
            <div class="grid grid-cols-7 gap-0.5 mb-5">
                @foreach ($weekDays as $day)
                    @php
                        $dayKey     = strtolower($day->format('l'));
                        $isToday    = $day->isToday();
                        $hasSession = $schedulesByDay->has($dayKey);
                    @endphp
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-[9px] font-bold text-faint uppercase">{{ $day->format('D') }}</span>
                        <div @class([
                            'w-7 h-7 rounded-full flex items-center justify-center',
                            'bg-navy text-white shadow-sm'  => $isToday,
                            'text-ink'                      => !$isToday,
                        ])>
                            <span class="text-[11px] font-bold">{{ $day->format('j') }}</span>
                        </div>
                        <div @class([
                            'w-1.5 h-1.5 rounded-full transition-all',
                            'bg-navy'    => $hasSession && $isToday,
                            'bg-navy/35' => $hasSession && !$isToday,
                            'invisible'  => !$hasSession,
                        ])></div>
                    </div>
                @endforeach
            </div>

            {{-- See full calendar --}}
            <div class="mb-4 text-center">
                <button wire:click="openCalendar"
                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-navy/70 hover:text-navy hover:underline underline-offset-2 transition-colors">
                    {{ __('messages.coach.dashboard.see_details') }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            {{-- Today's sessions --}}
            <div class="border-t border-line pt-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">
                    {{ __('messages.coach.dashboard.todays_sessions') }}
                </p>

                @if ($todaySchedules->isEmpty())
                    <div class="text-center py-3">
                        <p class="text-xs text-faint">{{ __('messages.coach.dashboard.no_sessions') }}</p>
                        @php
                            $dayOrder = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                            $todayIdx = array_search(strtolower(now()->format('l')), $dayOrder);
                            $nextDay  = null; $nextDaySchedules = collect();
                            for ($i = 1; $i <= 7; $i++) {
                                $dk = $dayOrder[($todayIdx + $i) % 7];
                                if ($schedulesByDay->has($dk)) { $nextDay = __('messages.coach.days.'.$dk); $nextDaySchedules = $schedulesByDay->get($dk); break; }
                            }
                        @endphp
                        @if ($nextDay)
                            <p class="text-[10px] text-navy/60 mt-1">{{ __('messages.coach.dashboard.next') }} <span class="font-semibold">{{ $nextDay }}</span></p>
                        @endif
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($todaySchedules as $sched)
                            <div class="flex items-start gap-2.5 group">
                                <div class="w-1.5 h-1.5 rounded-full bg-navy shrink-0 mt-1.5"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                    <p class="text-[10px] text-faint truncate">{{ $sched->location->name }}</p>
                                    <p class="text-[10px] text-muted font-semibold">
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}
                                    </p>
                                </div>
                                @if ($sched->type === 'private')
                                    <span class="text-[8px] font-bold uppercase bg-purple-100 text-purple-700 px-1 py-0.5 rounded-full shrink-0">{{ __('messages.coach.dashboard.private_badge') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Rest of the week --}}
            @php
                $dayOrder  = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                $dayLabels = ['monday' => __('messages.coach.days.monday'), 'tuesday' => __('messages.coach.days.tuesday'), 'wednesday' => __('messages.coach.days.wednesday'), 'thursday' => __('messages.coach.days.thursday'), 'friday' => __('messages.coach.days.friday'), 'saturday' => __('messages.coach.days.saturday'), 'sunday' => __('messages.coach.days.sunday')];
                $todayKey  = strtolower(now()->format('l'));
                $hasOther  = collect($dayOrder)->filter(fn($d) => $d !== $todayKey && $schedulesByDay->has($d))->isNotEmpty();
            @endphp
            @if ($hasOther)
                <div class="border-t border-line pt-4 mt-4 space-y-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-faint">{{ __('messages.coach.dashboard.rest_of_week') }}</p>
                    @foreach ($dayOrder as $dk)
                        @if ($dk !== $todayKey && $schedulesByDay->has($dk))
                            <div>
                                <p class="text-[10px] font-bold text-muted uppercase mb-1.5">{{ $dayLabels[$dk] }}</p>
                                <div class="space-y-1.5">
                                    @foreach ($schedulesByDay->get($dk) as $sched)
                                        <div class="flex items-center gap-2">
                                            <div class="w-1 h-1 rounded-full bg-navy/30 shrink-0"></div>
                                            <p class="text-[10px] text-ink truncate flex-1">{{ $sched->program->name }}</p>
                                            <p class="text-[9px] text-faint shrink-0">{{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    {{-- RIGHT: Main content --}}
    <div class="flex-1 min-w-0 space-y-6">

        {{-- Quick Actions --}}
        <x-card>
            <div class="flex items-center gap-2 mb-4">
                <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </span>
                <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.coach.dashboard.quick_actions') }}</p>
            </div>
            <div class="grid grid-cols-4 gap-2">
                @foreach ([
                    ['route' => 'coach.checkin',     'label' => __('messages.coach.nav.checkin'),      'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M14.121 11.879a3 3 0 10-4.242-4.242 3 3 0 004.242 4.242z'],
                    ['route' => 'coach.qr-scanner',  'label' => __('messages.coach.nav.qr_scanner'),  'icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z'],
                    ['route' => 'coach.attendance',  'label' => __('messages.nav.attendance'),         'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['route' => 'coach.roster',      'label' => __('messages.coach.nav.roster'),       'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['route' => 'coach.schedules',   'label' => __('messages.coach.nav.schedules'),    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['route' => 'coach.report-cards','label' => __('messages.nav.report_cards'),       'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ] as $item)
                    <a href="{{ route($item['route']) }}" class="flex flex-col items-center gap-1.5 text-center group">
                        <span class="w-12 h-12 rounded-2xl bg-navy/8 text-navy flex items-center justify-center group-hover:bg-navy/15 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"/>
                            </svg>
                        </span>
                        <span class="text-[11px] font-semibold text-ink leading-tight">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </x-card>

        {{-- Today's sessions detail --}}
        <x-card padding="p-0">
            <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                <h3 class="text-sm font-bold uppercase tracking-wide text-navy">{{ __('messages.coach.dashboard.todays_sessions') }}</h3>
                <span class="text-xs font-semibold text-faint">{{ now()->format('l, d F Y') }}</span>
            </div>

            @if ($todaySchedules->isEmpty())
                <x-empty-state :title="__('messages.coach.dashboard.empty_title')" :description="__('messages.coach.dashboard.day_off')" />
            @else
                <div class="divide-y divide-line">
                    @foreach ($todaySchedules as $schedule)
                        <div class="px-4 py-4 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-navy/8 flex items-center justify-center shrink-0 text-navy font-extrabold text-sm">
                                    {{ strtoupper(substr($schedule->program->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-semibold text-ink text-sm">{{ $schedule->program->name }}</p>
                                        @if ($schedule->type === 'private')
                                            <span class="text-[9px] font-bold uppercase bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full">{{ __('messages.coach.dashboard.private_badge') }}</span>
                                        @else
                                            <span class="text-[9px] font-bold uppercase bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full">{{ __('messages.coach.dashboard.regular_badge') }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-faint">{{ $schedule->location->name }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <p class="text-sm font-bold text-navy">
                                    {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                </p>
                                <p class="text-xs text-faint">{{ $schedule->approvedEnrollmentsCount() }}/{{ $schedule->max_capacity }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

    </div>
</div>{{-- end flex row --}}

    {{-- ════════ Month Calendar modal ════════ --}}
    @if ($showCalendar && $calendar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeCalendar"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            {{-- Header --}}
            <div class="sticky top-0 bg-surface flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-line z-10">
                <div>
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.coach.dashboard.session_calendar') }}</h3>
                    <p class="text-[11px] text-faint">{{ __('messages.coach.dashboard.sessions_weekly') }}</p>
                </div>
                <button wire:click="closeCalendar" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>

            <div class="p-4 sm:p-6">
                {{-- Month nav --}}
                <div class="flex items-center justify-between mb-4">
                    <button wire:click="prevMonth" wire:loading.attr="disabled"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-line text-ink hover:bg-off hover:border-navy/30 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </button>
                    <p class="text-base font-extrabold text-navy tracking-tight">{{ $calendar['label'] }}</p>
                    <button wire:click="nextMonth" wire:loading.attr="disabled"
                            class="w-9 h-9 flex items-center justify-center rounded-xl border border-line text-ink hover:bg-off hover:border-navy/30 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                </div>

                {{-- Weekday header --}}
                <div class="grid grid-cols-7 gap-0.5 sm:gap-1 mb-1.5">
                    @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $wd)
                        <p class="text-center text-[10px] font-bold uppercase tracking-wide text-faint">{{ $wd }}</p>
                    @endforeach
                </div>

                {{-- Day grid --}}
                <div class="space-y-0.5 sm:space-y-1">
                    @foreach ($calendar['weeks'] as $week)
                        <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
                            @foreach ($week as $cell)
                                @php $isSelected = $selectedDate === $cell['date']; @endphp
                                <button wire:click="selectDate('{{ $cell['date'] }}')"
                                        @class([
                                            'relative h-12 sm:h-16 rounded-lg sm:rounded-xl border flex flex-col items-center justify-start pt-1 sm:pt-1.5 gap-0.5 sm:gap-1 transition-colors',
                                            'border-navy bg-navy/[0.05]'               => $isSelected,
                                            'border-line hover:border-navy/30 hover:bg-off' => !$isSelected,
                                            'opacity-40'                               => !$cell['inMonth'],
                                        ])>
                                    <span @class([
                                        'w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-full text-[11px] sm:text-xs font-bold',
                                        'bg-navy text-white'        => $cell['isToday'],
                                        'text-ink'                  => !$cell['isToday'] && $cell['inMonth'],
                                        'text-faint'                => !$cell['inMonth'],
                                    ])>{{ $cell['day'] }}</span>
                                    @if ($cell['count'] > 0)
                                        <span class="inline-flex items-center justify-center min-w-[15px] h-[15px] sm:min-w-[18px] sm:h-[18px] px-0.5 sm:px-1 rounded-full bg-navy/10 text-navy text-[8px] sm:text-[9px] font-extrabold tabular-nums leading-none">
                                            {{ $cell['count'] }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                {{-- Selected day detail --}}
                <div class="mt-5 border-t border-line pt-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, d M Y') }}
                        <span class="text-navy/50">· {{ trans_choice('messages.coach.dashboard.sessions_count', $selectedSessions->count(), ['n' => $selectedSessions->count()]) }}</span>
                    </p>

                    @if ($selectedSessions->isEmpty())
                        <p class="text-xs text-faint py-2">{{ __('messages.coach.dashboard.no_sessions_day') }}</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($selectedSessions as $sched)
                                <div class="flex items-center gap-3 rounded-xl border border-line px-3 py-2.5">
                                    <div class="w-1.5 h-1.5 rounded-full shrink-0 {{ $sched->type === 'private' ? 'bg-[#7C3AED]' : 'bg-[#1D4ED8]' }}"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                            <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded-full {{ $sched->type === 'private' ? 'bg-[#7C3AED]/10 text-[#7C3AED]' : 'bg-[#1D4ED8]/10 text-[#1D4ED8]' }}">
                                                {{ $sched->type === 'private' ? __('messages.coach.dashboard.private_badge') : __('messages.coach.dashboard.regular_badge') }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-faint truncate">{{ $sched->location->name }}</p>
                                    </div>
                                    <p class="text-[11px] font-semibold text-muted tabular-nums shrink-0">
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

</div>{{-- end outer wrapper --}}
