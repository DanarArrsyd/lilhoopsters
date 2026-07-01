@props(['nextSession', 'weekSessions', 'showCalendar' => false, 'calendar' => null, 'selectedDate' => null, 'selectedSessions' => null])

<x-card class="mb-4">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </span>
            <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.next_session') }}</p>
        </div>
        <button wire:click="openCalendar" wire:loading.attr="disabled"
                class="lg:hidden inline-flex items-center gap-1.5 text-xs font-semibold text-navy bg-navy/8 px-2.5 py-1 rounded-lg hover:bg-navy/15 transition-colors shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            {{ __('messages.portal.home.calendar') }}
        </button>
    </div>

    @if ($nextSession)
        <div class="flex items-start justify-between"
             x-data="{ open: false }">
            <div>
                <p class="text-base font-semibold text-ink">{{ $nextSession['program'] }}</p>
                <p class="text-sm text-muted">
                    {{ $nextSession['coach'] ?? __('messages.portal.home.no_coach') }} · {{ $nextSession['location'] }}
                </p>
                <p class="text-xs text-faint mt-1">{{ $nextSession['date']->translatedFormat('l, d M') }}</p>
            </div>
            <span class="font-mono text-sm text-navy font-medium shrink-0">
                {{ \Illuminate\Support\Carbon::parse($nextSession['start'])->format('H:i') }}
            </span>
        </div>

        @if ($weekSessions->isNotEmpty())
            <div x-data="{ open: false }" class="mt-3 pt-3 border-t border-line">
                <button @click="open = !open" class="text-xs font-semibold text-navy">
                    <span x-show="!open">{{ __('messages.portal.home.view_week') }}</span>
                    <span x-show="open" x-cloak>{{ __('messages.portal.home.hide_week') }}</span>
                </button>
                <div x-show="open" x-collapse x-cloak class="mt-3 space-y-2">
                    @foreach ($weekSessions as $day => $sessions)
                        @foreach ($sessions as $session)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-ink capitalize">{{ ucfirst($day) }} — {{ $session['program'] }}</span>
                                <span class="font-mono text-muted">{{ \Illuminate\Support\Carbon::parse($session['start'])->format('H:i') }}</span>
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <p class="text-sm text-muted">{{ __('messages.portal.home.no_session') }}</p>
    @endif
</x-card>

{{-- ════════ Month Calendar modal ════════ --}}
@if ($showCalendar && $calendar)
<div class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4">
    <div class="absolute inset-0 bg-navy/40" wire:click="closeCalendar"></div>
    <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-surface flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-line z-10">
            <div>
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.portal.home.calendar') }}</h3>
                <p class="text-[11px] text-faint">Sessions repeat weekly by day.</p>
            </div>
            <button wire:click="closeCalendar" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
        </div>

        <div class="p-4 sm:p-6">
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

            <div class="grid grid-cols-7 gap-0.5 sm:gap-1 mb-1.5">
                @foreach (['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $wd)
                    <p class="text-center text-[10px] font-bold uppercase tracking-wide text-faint">{{ $wd }}</p>
                @endforeach
            </div>

            <div class="space-y-0.5 sm:space-y-1">
                @foreach ($calendar['weeks'] as $week)
                    <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
                        @foreach ($week as $cell)
                            @php $isSelected = $selectedDate === $cell['date']; @endphp
                            <button wire:click="selectDate('{{ $cell['date'] }}')"
                                    @class([
                                        'relative h-12 sm:h-16 rounded-lg sm:rounded-xl border flex flex-col items-center justify-start pt-1 sm:pt-1.5 gap-0.5 sm:gap-1 transition-colors',
                                        'border-navy bg-navy/[0.05]'                    => $isSelected,
                                        'border-line hover:border-navy/30 hover:bg-off' => !$isSelected,
                                        'opacity-40'                                    => !$cell['inMonth'],
                                    ])>
                                <span @class([
                                    'w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-full text-[11px] sm:text-xs font-bold',
                                    'bg-navy text-white' => $cell['isToday'],
                                    'text-ink'           => !$cell['isToday'] && $cell['inMonth'],
                                    'text-faint'         => !$cell['inMonth'],
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

            <div class="mt-5 border-t border-line pt-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">
                    {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('l, d M Y') }}
                    <span class="text-navy/50">· {{ $selectedSessions->count() }} session{{ $selectedSessions->count() === 1 ? '' : 's' }}</span>
                </p>

                @if ($selectedSessions->isEmpty())
                    <p class="text-xs text-faint py-2">{{ __('messages.portal.home.no_sessions_day') }}</p>
                @else
                    <div class="space-y-2">
                        @foreach ($selectedSessions as $enr)
                            <div class="flex items-center gap-3 rounded-xl border border-line px-3 py-2.5">
                                <div class="w-1.5 h-1.5 rounded-full shrink-0 {{ $enr->schedule->type === 'private' ? 'bg-[#7C3AED]' : 'bg-[#1D4ED8]' }}"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-bold text-ink truncate">{{ $enr->schedule->program->name }}</p>
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded-full {{ $enr->schedule->type === 'private' ? 'bg-[#7C3AED]/10 text-[#7C3AED]' : 'bg-[#1D4ED8]/10 text-[#1D4ED8]' }}">
                                            {{ $enr->schedule->type === 'private' ? 'Private' : 'Regular' }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-faint truncate">{{ $enr->schedule->location->name }}</p>
                                </div>
                                <p class="text-[11px] font-semibold text-muted tabular-nums shrink-0">
                                    {{ \Illuminate\Support\Carbon::parse($enr->schedule->start_time)->format('H:i') }}
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
