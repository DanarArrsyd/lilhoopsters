@props(['calendar', 'selectedDate', 'selectedSessions'])

<x-card>
    <div class="flex items-center gap-2 mb-4">
        <span class="w-7 h-7 rounded-lg bg-navy/8 text-navy flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </span>
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.calendar') }}</p>
    </div>

    @if ($calendar)
        <div class="flex items-center justify-between mb-3">
            <button wire:click="prevMonth" wire:loading.attr="disabled"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-line text-ink hover:bg-off hover:border-navy/30 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <p class="text-sm font-extrabold text-navy tracking-tight">{{ $calendar['label'] }}</p>
            <button wire:click="nextMonth" wire:loading.attr="disabled"
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-line text-ink hover:bg-off hover:border-navy/30 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        <div class="grid grid-cols-7 gap-0.5 mb-1.5">
            @foreach (['Mo','Tu','We','Th','Fr','Sa','Su'] as $wd)
                <p class="text-center text-[9px] font-bold uppercase tracking-wide text-faint">{{ $wd }}</p>
            @endforeach
        </div>

        <div class="space-y-0.5">
            @foreach ($calendar['weeks'] as $week)
                <div class="grid grid-cols-7 gap-0.5">
                    @foreach ($week as $cell)
                        @php $isSelected = $selectedDate === $cell['date']; @endphp
                        <button wire:click="selectDate('{{ $cell['date'] }}')"
                                @class([
                                    'relative h-10 rounded-lg border flex flex-col items-center justify-start pt-1 gap-0.5 transition-colors',
                                    'border-navy bg-navy/[0.05]'                    => $isSelected,
                                    'border-line hover:border-navy/30 hover:bg-off' => !$isSelected,
                                    'opacity-40'                                    => !$cell['inMonth'],
                                ])>
                            <span @class([
                                'w-4 h-4 flex items-center justify-center rounded-full text-[9px] font-bold',
                                'bg-navy text-white' => $cell['isToday'],
                                'text-ink'           => !$cell['isToday'] && $cell['inMonth'],
                                'text-faint'         => !$cell['inMonth'],
                            ])>{{ $cell['day'] }}</span>
                            @if ($cell['count'] > 0)
                                <span class="inline-flex items-center justify-center min-w-[12px] h-[12px] px-0.5 rounded-full bg-navy/10 text-navy text-[7px] font-extrabold tabular-nums leading-none">
                                    {{ $cell['count'] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="mt-4 border-t border-line pt-3">
            <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-2">
                {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('l, d M') }}
            </p>

            @if ($selectedSessions->isEmpty())
                <p class="text-xs text-faint py-1">{{ __('messages.portal.home.no_sessions_day') }}</p>
            @else
                <div class="space-y-1.5">
                    @foreach ($selectedSessions as $enr)
                        <div class="flex items-center gap-2 rounded-lg border border-line px-2.5 py-2">
                            <div class="w-1.5 h-1.5 rounded-full shrink-0 {{ $enr->schedule->type === 'private' ? 'bg-[#7C3AED]' : 'bg-[#1D4ED8]' }}"></div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-bold text-ink truncate">{{ $enr->schedule->program->name }}</p>
                                <p class="text-[10px] text-faint truncate">{{ $enr->schedule->location->name }}</p>
                            </div>
                            <p class="text-[10px] font-semibold text-muted tabular-nums shrink-0">
                                {{ \Illuminate\Support\Carbon::parse($enr->schedule->start_time)->format('H:i') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</x-card>
