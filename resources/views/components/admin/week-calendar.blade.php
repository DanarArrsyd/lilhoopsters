@props(['weekDays', 'schedulesByDay', 'todaySchedules'])

<x-card padding="p-5">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm font-extrabold text-navy uppercase tracking-tight">{{ __('messages.admin.dashboard.this_week') }}</p>
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
            {{ __('messages.admin.dashboard.see_details') }}
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    {{-- Today's sessions --}}
    <div class="border-t border-line pt-4">
        <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">
            {{ __('messages.admin.dashboard.todays_sessions') }}
        </p>

        @if ($todaySchedules->isEmpty())
            <div class="text-center py-3">
                <p class="text-xs text-faint">{{ __('messages.admin.dashboard.no_sessions') }}</p>
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
                    <p class="text-[10px] text-navy/60 mt-1">{{ __('messages.admin.dashboard.next') }} <span class="font-semibold">{{ $nextDay }}</span></p>
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
        $dayLabels = ['monday'=>__('messages.coach.days.monday'),'tuesday'=>__('messages.coach.days.tuesday'),'wednesday'=>__('messages.coach.days.wednesday'),'thursday'=>__('messages.coach.days.thursday'),'friday'=>__('messages.coach.days.friday'),'saturday'=>__('messages.coach.days.saturday'),'sunday'=>__('messages.coach.days.sunday')];
        $todayKey  = strtolower(now()->format('l'));
        $hasOther  = collect($dayOrder)->filter(fn($d) => $d !== $todayKey && $schedulesByDay->has($d))->isNotEmpty();
    @endphp
    @if ($hasOther)
        <div class="border-t border-line pt-4 mt-4" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between lg:pointer-events-none">
                <p class="text-[10px] font-bold uppercase tracking-widest text-faint">{{ __('messages.admin.dashboard.rest_of_week') }}</p>
                <span class="flex items-center gap-1 text-[10px] font-semibold text-navy/70 lg:hidden">
                    {{ __('messages.admin.dashboard.see_details') }}
                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            </button>
            <div x-show="open" x-collapse class="lg:!block space-y-3 mt-3">
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
        </div>
    @endif
</x-card>
