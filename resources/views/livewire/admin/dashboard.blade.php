<div class="max-w-6xl mx-auto">

    <x-admin.page-header
        :title="__('messages.admin.dashboard.title')"
        :subtitle="__('messages.admin.dashboard.subtitle')" />

{{-- What is waiting on staff, and how big the academy is. These counts
     were already being computed on every mount and thrown away — the page
     rendered a twelve-tile launcher instead, which the sidebar now covers. --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    <x-admin.stat-tile kind="queue"
        :label="__('messages.admin.dashboard.stat_pending_payments')"
        :value="$stats['pending_payments'] ?? 0"
        :href="route('admin.payments')"
        :hint="($stats['pending_payments'] ?? 0) > 0 ? __('messages.admin.dashboard.stat_open_list') : __('messages.admin.dashboard.stat_all_clear')" />

    <x-admin.stat-tile kind="queue"
        :label="__('messages.admin.dashboard.stat_pending_enrollments')"
        :value="$stats['pending_enrollments'] ?? 0"
        :href="route('admin.enrollments')"
        :hint="($stats['pending_enrollments'] ?? 0) > 0 ? __('messages.admin.dashboard.stat_open_list') : __('messages.admin.dashboard.stat_all_clear')" />

    <x-admin.stat-tile kind="queue"
        :label="__('messages.admin.dashboard.stat_pending_leaves')"
        :value="$stats['pending_leaves'] ?? 0"
        :href="route('admin.leave-requests')"
        :hint="($stats['pending_leaves'] ?? 0) > 0 ? __('messages.admin.dashboard.stat_open_list') : __('messages.admin.dashboard.stat_all_clear')" />

    <x-admin.stat-tile
        :label="__('messages.admin.dashboard.stat_active_players')"
        :value="$stats['active_players'] ?? 0"
        :href="route('admin.players')"
        :hint="__('messages.admin.dashboard.stat_open_list')" />
</div>

<div class="flex flex-col lg:flex-row gap-6 items-start">

    {{-- LEFT: Weekly Calendar --}}
    <div class="w-full lg:w-72 shrink-0 lg:sticky lg:top-20">
        <x-admin.week-calendar :week-days="$weekDays" :schedules-by-day="$schedulesByDay" :today-schedules="$todaySchedules" />
    </div>

    {{-- RIGHT: Main content --}}
    <div class="flex-1 min-w-0 space-y-6">


        {{-- Today's Activity — all locations monitoring --}}
        <x-card padding="p-0">
            <div class="px-4 py-3 border-b border-line flex items-center justify-between">
                <h3 class="text-sm font-bold uppercase tracking-wide text-navy">{{ __('messages.admin.dashboard.todays_activity') }}</h3>
                <span class="text-xs font-semibold text-faint">{{ now()->format('l, d F Y') }}</span>
            </div>
            @if ($todayActivity->isEmpty())
                <x-empty-state :title="__('messages.admin.dashboard.no_sessions_title')" :description="__('messages.admin.dashboard.no_sessions')" />
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
                                            <span class="text-4xs font-bold uppercase bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full">{{ __('messages.admin.dashboard.private_badge') }}</span>
                                        @else
                                            <span class="text-4xs font-bold uppercase bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded-full">{{ __('messages.admin.dashboard.regular_badge') }}</span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-faint">{{ $s->program->name }} · {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}</p>
                                    @if ($s->type === 'private' && $s->coach)
                                        <p class="text-3xs text-muted">{{ __('messages.admin.dashboard.coach_label') }} {{ $s->coach->user->name }}</p>
                                    @endif
                                </div>
                            </div>
                            <div class="text-right shrink-0 space-y-0.5">
                                <p class="text-sm font-bold text-ink">
                                    {{ $item['present'] }}<span class="text-faint font-normal">/{{ $item['enrolled'] }}</span>
                                </p>
                                <p class="text-3xs text-faint">
                                    {{ __('messages.admin.dashboard.present_recorded', ['a' => $item['present'], 'b' => $item['recorded']]) }}
                                </p>
                                @if ($item['enrolled'] > 0 && $item['recorded'] === 0)
                                    <span class="text-4xs font-bold uppercase text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded-full">{{ __('messages.admin.dashboard.not_started') }}</span>
                                @elseif ($item['recorded'] >= $item['enrolled'] && $item['enrolled'] > 0)
                                    <span class="text-4xs font-bold uppercase text-green-600 bg-green-50 px-1.5 py-0.5 rounded-full">{{ __('messages.admin.dashboard.complete') }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>

    </div>
</div>{{-- end flex --}}

    {{-- ════════ Month Calendar modal ════════ --}}
    @if ($showCalendar && $calendar)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeCalendar"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            {{-- Header --}}
            <div class="sticky top-0 bg-surface flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-line z-10">
                <div>
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.dashboard.session_calendar') }}</h3>
                    <p class="text-2xs text-faint">{{ __('messages.admin.dashboard.sessions_weekly') }}</p>
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
                        <p class="text-center text-3xs font-bold uppercase tracking-wide text-faint">{{ $wd }}</p>
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
                                        'w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-full text-2xs sm:text-xs font-bold',
                                        'bg-navy text-white'        => $cell['isToday'],
                                        'text-ink'                  => !$cell['isToday'] && $cell['inMonth'],
                                        'text-faint'                => !$cell['inMonth'],
                                    ])>{{ $cell['day'] }}</span>
                                    @if ($cell['count'] > 0)
                                        <span class="inline-flex items-center justify-center min-w-[15px] h-[15px] sm:min-w-[18px] sm:h-[18px] px-0.5 sm:px-1 rounded-full bg-navy/10 text-navy text-4xs sm:text-4xs font-extrabold tabular-nums leading-none">
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
                    <p class="text-3xs font-bold uppercase tracking-widest text-faint mb-3">
                        {{ \Carbon\Carbon::parse($selectedDate)->format('l, d M Y') }}
                        <span class="text-navy/50">· {{ $selectedSessions->count() }} session{{ $selectedSessions->count() === 1 ? '' : 's' }}</span>
                    </p>

                    @if ($selectedSessions->isEmpty())
                        <p class="text-xs text-faint py-2">{{ __('messages.admin.dashboard.no_sessions_day') }}</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($selectedSessions as $sched)
                                <div class="flex items-center gap-3 rounded-xl border border-line px-3 py-2.5">
                                    <div class="w-1.5 h-1.5 rounded-full shrink-0 {{ $sched->type === 'private' ? 'bg-[#7C3AED]' : 'bg-[#1D4ED8]' }}"></div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <p class="text-sm font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                            <span class="text-4xs font-bold uppercase px-1.5 py-0.5 rounded-full {{ $sched->type === 'private' ? 'bg-[#7C3AED]/10 text-[#7C3AED]' : 'bg-[#1D4ED8]/10 text-[#1D4ED8]' }}">
                                                {{ $sched->type === 'private' ? __('messages.admin.dashboard.private_badge') : __('messages.admin.dashboard.regular_badge') }}
                                            </span>
                                        </div>
                                        <p class="text-2xs text-faint truncate">
                                            {{ $sched->location->name }}
                                            @if ($sched->type === 'private' && $sched->coach) · Coach {{ $sched->coach->user->name }} @endif
                                        </p>
                                    </div>
                                    <p class="text-2xs font-semibold text-muted tabular-nums shrink-0">
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
