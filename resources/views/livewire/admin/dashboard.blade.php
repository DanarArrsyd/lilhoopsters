<div class="space-y-6">

    {{-- Page header --}}
    <div>
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Dashboard</h2>
        <p class="text-sm text-muted">Overview of academy activity</p>
    </div>

<div class="flex flex-col lg:flex-row gap-6 items-start">

    {{-- LEFT: Weekly Calendar --}}
    <div class="w-full lg:w-68 shrink-0 lg:sticky lg:top-6">
        <x-card padding="p-5">
            {{-- Header --}}
            <div class="flex items-center justify-between mb-4">
                <p class="text-sm font-extrabold text-navy uppercase tracking-tight">This Week</p>
                <p class="text-[11px] text-faint font-semibold">{{ now()->format('d M Y') }}</p>
            </div>

            {{-- Day strip --}}
            <div class="grid grid-cols-7 gap-0.5 mb-5">
                @foreach ($weekDays as $day)
                    @php
                        $dayKey     = strtolower($day->format('l'));
                        $isToday    = $day->isToday();
                        $hasSession = $schedulesByDay->has($dayKey);
                        $count      = $hasSession ? $schedulesByDay->get($dayKey)->count() : 0;
                    @endphp
                    <div class="flex flex-col items-center gap-1">
                        <span class="text-[9px] font-bold text-faint uppercase">{{ $day->format('D') }}</span>
                        <div @class([
                            'w-7 h-7 rounded-full flex items-center justify-center',
                            'bg-navy text-white shadow-sm' => $isToday,
                            'text-ink'                     => !$isToday,
                        ])>
                            <span class="text-[11px] font-bold">{{ $day->format('j') }}</span>
                        </div>
                        <div @class([
                            'w-1.5 h-1.5 rounded-full',
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
                    See details
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>

            {{-- Today's sessions --}}
            <div class="border-t border-line pt-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">
                    {{ now()->format('l') }}'s Sessions
                </p>

                @if ($todaySchedules->isEmpty())
                    <div class="text-center py-3">
                        <p class="text-xs text-faint">No sessions today.</p>
                        @php
                            $dayOrder = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                            $todayIdx = array_search(strtolower(now()->format('l')), $dayOrder);
                            $nextDay  = null;
                            for ($i = 1; $i <= 7; $i++) {
                                $dk = $dayOrder[($todayIdx + $i) % 7];
                                if ($schedulesByDay->has($dk)) { $nextDay = ucfirst($dk); break; }
                            }
                        @endphp
                        @if ($nextDay)
                            <p class="text-[10px] text-navy/60 mt-1">Next: <span class="font-semibold">{{ $nextDay }}</span></p>
                        @endif
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($todaySchedules as $sched)
                            <div class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-navy shrink-0 mt-1.5"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                    <p class="text-[10px] text-faint truncate">{{ $sched->location->name }}</p>
                                    <p class="text-[10px] text-muted font-semibold">
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}
                                    </p>
                                </div>
                                @if ($sched->type === 'private')
                                    <span class="text-[8px] font-bold uppercase bg-purple-100 text-purple-700 px-1 py-0.5 rounded-full shrink-0">PVT</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Rest of week --}}
            @php
                $dayOrder  = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
                $dayLabels = ['monday'=>'Monday','tuesday'=>'Tuesday','wednesday'=>'Wednesday','thursday'=>'Thursday','friday'=>'Friday','saturday'=>'Saturday','sunday'=>'Sunday'];
                $todayKey  = strtolower(now()->format('l'));
                $hasOther  = collect($dayOrder)->filter(fn($d) => $d !== $todayKey && $schedulesByDay->has($d))->isNotEmpty();
            @endphp
            @if ($hasOther)
                <div class="border-t border-line pt-4 mt-4 space-y-3">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-faint">Rest of Week</p>
                    @foreach ($dayOrder as $dk)
                        @if ($dk !== $todayKey && $schedulesByDay->has($dk))
                            <div>
                                <p class="text-[10px] font-bold text-muted uppercase mb-1.5">{{ $dayLabels[$dk] }}</p>
                                <div class="space-y-1">
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

        {{-- Quick Access --}}
        <div>
            <p class="text-[10px] font-bold uppercase tracking-widest text-muted mb-3">Quick Access</p>
            <div class="grid grid-cols-4 sm:grid-cols-5 lg:grid-cols-4 gap-2 sm:gap-3">
                @foreach ([
                    ['route' => 'admin.parents',       'label' => 'Parents',        'bg' => 'bg-blue-50',     'color' => 'text-blue-700',   'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                    ['route' => 'admin.players',       'label' => 'Players',        'bg' => 'bg-teal-50',     'color' => 'text-teal-700',   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                    ['route' => 'admin.coaches',       'label' => 'Coaches',        'bg' => 'bg-purple-50',   'color' => 'text-purple-700', 'icon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                    ['route' => 'admin.enrollments',   'label' => 'Enrollments',    'bg' => 'bg-green-50',    'color' => 'text-green-700',  'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['route' => 'admin.payments',      'label' => 'Payments',       'bg' => 'bg-navy/8',      'color' => 'text-navy',       'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['route' => 'admin.attendances',   'label' => 'Attendances',    'bg' => 'bg-indigo-50',   'color' => 'text-indigo-700', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
                    ['route' => 'admin.leave-requests','label' => 'Leave Requests', 'bg' => 'bg-amber-50',    'color' => 'text-amber-700',  'icon' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
                    ['route' => 'admin.schedules',     'label' => 'Schedules',      'bg' => 'bg-sky-50',      'color' => 'text-sky-700',    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                    ['route' => 'admin.locations',     'label' => 'Locations',      'bg' => 'bg-rose-50',     'color' => 'text-rose-700',   'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M14.121 11.879a3 3 0 10-4.242-4.242 3 3 0 004.242 4.242z'],
                    ['route' => 'admin.programs',      'label' => 'Programs',       'bg' => 'bg-cyan-50',     'color' => 'text-cyan-700',   'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10'],
                    ['route' => 'admin.packages',      'label' => 'Packages',       'bg' => 'bg-orange-50',   'color' => 'text-orange-700', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                    ['route' => 'admin.makeup-classes','label' => 'Make-Up',        'bg' => 'bg-violet-50',   'color' => 'text-violet-700', 'icon' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'],
                ] as $item)
                    <a href="{{ route($item['route']) }}"
                       class="flex flex-col items-center gap-1.5 group p-1.5 sm:p-2 rounded-2xl hover:bg-off transition-colors">
                        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl {{ $item['bg'] }} flex items-center justify-center group-hover:scale-105 transition-transform duration-150 shrink-0">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 {{ $item['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $item['icon'] }}"/>
                            </svg>
                        </div>
                        <span class="text-[10px] font-semibold text-ink text-center leading-tight">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Today's Activity — all locations monitoring --}}
        <x-card padding="p-0">
            <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                <h3 class="text-sm font-bold uppercase tracking-wide text-navy">Today's Activity</h3>
                <span class="text-xs font-semibold text-faint">{{ now()->format('l, d F Y') }}</span>
            </div>
            @if ($todayActivity->isEmpty())
                <x-empty-state title="No sessions today" description="No active schedules on {{ now()->format('l') }}." />
            @else
                <div class="divide-y divide-line">
                    @foreach ($todayActivity as $item)
                        @php $s = $item['schedule']; @endphp
                        <div class="px-4 py-3 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-xl bg-navy/8 flex items-center justify-center shrink-0 text-navy font-extrabold text-sm">
                                    {{ strtoupper(substr($s->location->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="font-semibold text-ink text-sm">{{ $s->location->name }}</p>
                                        @if ($s->type === 'private')
                                            <span class="text-[9px] font-bold uppercase bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full">Private</span>
                                        @else
                                            <span class="text-[9px] font-bold uppercase bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full">Regular</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-faint">{{ $s->program->name }} · {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}</p>
                                    @if ($s->type === 'private' && $s->coach)
                                        <p class="text-[10px] text-muted">Coach: {{ $s->coach->user->name }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0 space-y-0.5">
                                <p class="text-sm font-bold text-ink">
                                    {{ $item['present'] }}<span class="text-faint font-normal">/{{ $item['enrolled'] }}</span>
                                </p>
                                <p class="text-[10px] text-faint">
                                    present · {{ $item['recorded'] }} recorded
                                </p>
                                @if ($item['enrolled'] > 0 && $item['recorded'] === 0)
                                    <span class="text-[9px] font-bold uppercase text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full">Not started</span>
                                @elseif ($item['recorded'] >= $item['enrolled'] && $item['enrolled'] > 0)
                                    <span class="text-[9px] font-bold uppercase text-green-600 bg-green-50 px-1.5 py-0.5 rounded-full">Complete</span>
                                @endif
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
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Session Calendar</h3>
                    <p class="text-[11px] text-faint">Sessions repeat weekly by day.</p>
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
                <div class="grid grid-cols-7 gap-1 mb-1.5">
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
                        <span class="text-navy/50">· {{ $selectedSessions->count() }} session{{ $selectedSessions->count() === 1 ? '' : 's' }}</span>
                    </p>

                    @if ($selectedSessions->isEmpty())
                        <p class="text-xs text-faint py-2">No sessions scheduled on this day.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($selectedSessions as $sched)
                                <div class="flex items-center gap-3 rounded-xl border border-line px-3 py-2.5">
                                    <div class="w-1.5 h-1.5 rounded-full shrink-0 {{ $sched->type === 'private' ? 'bg-[#7C3AED]' : 'bg-[#1D4ED8]' }}"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                            <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded-full {{ $sched->type === 'private' ? 'bg-[#7C3AED]/10 text-[#7C3AED]' : 'bg-[#1D4ED8]/10 text-[#1D4ED8]' }}">
                                                {{ $sched->type === 'private' ? 'Private' : 'Regular' }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-faint truncate">
                                            {{ $sched->location->name }}
                                            @if ($sched->type === 'private' && $sched->coach) · Coach {{ $sched->coach->user->name }} @endif
                                        </p>
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
