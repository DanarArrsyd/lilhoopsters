@props([
    'weekStrip',
    'weekSessions',
    'showCalendar' => false,
    'calendar' => null,
    'selectedDate' => null,
    'selectedSessions' => null,
])

@php
    $dayOrder  = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    $todayKey  = strtolower(now()->format('l'));
    $todaySessions = $weekSessions->get($todayKey) ?? collect();
    $hasOther  = collect($dayOrder)->filter(fn ($d) => $d !== $todayKey && ($weekSessions->get($d)?->isNotEmpty() ?? false))->isNotEmpty();

    // Next upcoming day this week that has sessions (for the day-off hint).
    $todayIdx  = array_search($todayKey, $dayOrder);
    $nextDay   = null; $nextDaySessions = collect();
    for ($i = 1; $i <= 7; $i++) {
        $dk = $dayOrder[($todayIdx + $i) % 7];
        if ($weekSessions->get($dk)?->isNotEmpty()) {
            $nextDay = __('messages.coach.days.'.$dk);
            break;
        }
    }
@endphp

<x-card padding="p-5" tone="flat">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <p class="text-sm font-extrabold text-navy uppercase tracking-tight">{{ __('messages.portal.home.this_week') }}</p>
        <p class="text-2xs text-faint font-semibold">{{ now()->format('d M Y') }}</p>
    </div>

    {{-- Day strip --}}
    <div class="grid grid-cols-7 gap-0.5 mb-5">
        @foreach ($weekStrip as $d)
            <button wire:click="selectDate('{{ $d['date'] }}')"
                    class="flex flex-col items-center gap-1 group">
                <span class="text-4xs font-bold text-faint uppercase">{{ $d['label'] }}</span>
                <span @class([
                    'w-7 h-7 rounded-full flex items-center justify-center text-2xs font-bold',
                    'bg-navy text-white shadow-sm' => $d['isToday'],
                    'text-ink group-hover:bg-off'  => !$d['isToday'],
                ])>{{ $d['day'] }}</span>
                <span @class([
                    'w-1.5 h-1.5 rounded-full transition-all',
                    'bg-navy'    => $d['count'] > 0 && $d['isToday'],
                    'bg-navy/35' => $d['count'] > 0 && !$d['isToday'],
                    'invisible'  => $d['count'] === 0,
                ])></span>
            </button>
        @endforeach
    </div>

    {{-- See full calendar --}}
    <div class="mb-4 text-center">
        <button wire:click="openCalendar" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1 text-2xs font-semibold text-navy/70 hover:text-navy hover:underline underline-offset-2 transition-colors">
            {{ __('messages.portal.home.see_details') }}
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    {{-- Today's sessions --}}
    <div class="border-t border-line pt-4">
        <p class="text-3xs font-bold uppercase tracking-widest text-faint mb-3">
            {{ __('messages.portal.home.todays_sessions') }}
        </p>

        @if ($todaySessions->isEmpty())
            <div class="text-center py-3">
                <p class="text-xs text-faint">{{ __('messages.portal.home.no_session') }}</p>
                @if ($nextDay)
                    <p class="text-3xs text-navy/60 mt-1">{{ __('messages.portal.home.next') }} <span class="font-semibold">{{ $nextDay }}</span></p>
                @endif
            </div>
        @else
            <div class="space-y-3">
                @foreach ($todaySessions as $sess)
                    <div class="flex items-start gap-2.5">
                        <div class="w-1.5 h-1.5 rounded-full bg-navy shrink-0 mt-1.5"></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-ink truncate">{{ $sess['program'] }}</p>
                            <p class="text-3xs text-faint truncate flex items-center gap-1">
                                <span class="truncate">{{ $sess['location'] }}</span>
                                <x-maps-button :url="$sess['location_maps_url']" />
                            </p>
                            <p class="text-3xs text-muted font-semibold">
                                {{ \Illuminate\Support\Carbon::parse($sess['start'])->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($sess['end'])->format('H:i') }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Rest of week (collapsible on mobile, always open on desktop) --}}
    @if ($hasOther)
        <div class="border-t border-line pt-4 mt-4" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="w-full flex items-center justify-between lg:pointer-events-none">
                <p class="text-3xs font-bold uppercase tracking-widest text-faint">{{ __('messages.portal.home.rest_of_week') }}</p>
                <span class="flex items-center gap-1 text-3xs font-semibold text-navy/70 lg:hidden">
                    {{ __('messages.portal.home.see_details') }}
                    <svg class="w-3 h-3 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            </button>
            <div x-show="open" x-collapse class="lg:!block space-y-4 mt-4">
                @foreach ($dayOrder as $dk)
                    @php $daySessions = $weekSessions->get($dk); @endphp
                    @if ($dk !== $todayKey && $daySessions?->isNotEmpty())
                        <div>
                            <p class="text-3xs font-bold text-muted uppercase mb-1.5">{{ __('messages.coach.days.'.$dk) }}</p>
                            <div class="space-y-1.5">
                                @foreach ($daySessions as $sess)
                                    <div class="flex items-center gap-2">
                                        <div class="w-1 h-1 rounded-full bg-navy/30 shrink-0"></div>
                                        <p class="text-3xs text-ink truncate flex-1">{{ $sess['program'] }}</p>
                                        <p class="text-4xs text-faint shrink-0">{{ \Illuminate\Support\Carbon::parse($sess['start'])->format('H:i') }}</p>
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

{{-- ════════ Month Calendar modal ════════ --}}
@if ($showCalendar && $calendar)
{{-- The padding reserves the chrome this modal sits on top of: the 3.5rem
     sticky header above, and the bottom nav below on anything under lg. With
     max-h-[90vh] the panel only left 5vh of headroom, which is less than the
     header is tall, so a full month of sessions slid up underneath it.
     max-h-full then measures against this padded box, so the panel keeps
     clearing both bars even if either one changes height. --}}
<div class="fixed inset-0 z-50 flex items-center justify-center px-3 sm:px-4 pt-[4.5rem] pb-20 lg:pb-4">
    <div class="absolute inset-0 bg-navy/40" wire:click="closeCalendar"></div>
    <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-2xl lg:max-w-4xl max-h-full overflow-y-auto">
        <div class="sticky top-0 bg-surface flex items-center justify-between px-4 sm:px-6 py-3.5 sm:py-4 border-b border-line z-10">
            <div>
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.portal.home.calendar') }}</h3>
                <p class="text-2xs text-faint">Sessions repeat weekly by day.</p>
            </div>
            <button wire:click="closeCalendar" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
        </div>

        @php
            // One colour per day state. A parent opening the calendar wants to know
            // what happened, not how many rows matched — so the cell carries the
            // outcome and the count moves to the day panel.
            $stateDot = [
                'scheduled' => 'bg-navy',
                'present'   => 'bg-[#15803D]',
                'missed'    => 'bg-[#B91C1C]',
                'excused'   => 'bg-[#1D4ED8]',
                'pending'   => 'border-2 border-navy/50 bg-transparent',
                'makeup'    => 'bg-[#7C3AED]',
            ];
            $stateLabel = [
                'scheduled' => __('messages.portal.home.state_scheduled'),
                'present'   => __('messages.portal.home.state_present'),
                'missed'    => __('messages.portal.home.state_missed'),
                'excused'   => __('messages.portal.home.state_excused'),
                'pending'   => __('messages.portal.home.state_pending'),
                'makeup'    => __('messages.portal.home.state_makeup'),
            ];
        @endphp

        <div class="p-4 sm:p-6 lg:grid lg:grid-cols-[1fr_20rem] lg:gap-6 lg:items-start">
          <div>
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
                    <p class="text-center text-3xs font-bold uppercase tracking-wide text-faint">{{ $wd }}</p>
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
                                    'w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-full text-2xs sm:text-xs font-bold',
                                    'bg-navy text-white' => $cell['isToday'],
                                    'text-ink'           => !$cell['isToday'] && $cell['inMonth'],
                                    'text-faint'         => !$cell['inMonth'],
                                ])>{{ $cell['day'] }}</span>

                                @if ($cell['state'] !== 'none')
                                    <span class="w-2 h-2 rounded-full {{ $stateDot[$cell['state']] }}"
                                          title="{{ $stateLabel[$cell['state']] }}"></span>
                                @endif

                                @if ($cell['hasEvent'])
                                    <span class="absolute top-1 right-1 w-1.5 h-1.5 rounded-full bg-[#B45309]"
                                          title="{{ __('messages.portal.home.state_event') }}"></span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Legend: the dots are only readable if the key is next to them --}}
            <div class="mt-4 flex flex-wrap items-center gap-x-3 gap-y-1.5">
                @foreach ($stateLabel as $key => $text)
                    <span class="inline-flex items-center gap-1.5 text-3xs text-muted">
                        <span class="w-2 h-2 rounded-full {{ $stateDot[$key] }}"></span>{{ $text }}
                    </span>
                @endforeach
                <span class="inline-flex items-center gap-1.5 text-3xs text-muted">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#B45309]"></span>{{ __('messages.portal.home.state_event') }}
                </span>
            </div>
          </div>

          {{-- Day panel. On desktop it sits beside the grid and stays put, so
               clicking through dates doesn't push the month around. --}}
          <div class="mt-5 lg:mt-0 border-t lg:border-t-0 lg:border-l border-line pt-4 lg:pt-0 lg:pl-6">
                <p class="text-3xs font-bold uppercase tracking-widest text-faint mb-2">
                    {{ \Illuminate\Support\Carbon::parse($selectedDate)->format('l, d M Y') }}
                    <span class="text-navy/50">· {{ $selectedSessions->count() }} session{{ $selectedSessions->count() === 1 ? '' : 's' }}</span>
                </p>

                {{-- The dot in the grid says what happened; repeat it in words here,
                     otherwise the colour is a code the parent has to decrypt. --}}
                @php
                    $selectedCell = collect($calendar['weeks'])->flatten(1)->firstWhere('date', $selectedDate);
                @endphp
                @if ($selectedCell && $selectedCell['state'] !== 'none')
                    <p class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-off px-2.5 py-1 text-2xs font-semibold text-ink">
                        <span class="w-2 h-2 rounded-full {{ $stateDot[$selectedCell['state']] }}"></span>
                        {{ $stateLabel[$selectedCell['state']] }}
                    </p>
                @endif

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
                                        <span class="text-4xs font-bold uppercase px-1.5 py-0.5 rounded-full {{ $enr->schedule->type === 'private' ? 'bg-[#7C3AED]/10 text-[#7C3AED]' : 'bg-[#1D4ED8]/10 text-[#1D4ED8]' }}">
                                            {{ $enr->schedule->type === 'private' ? 'Private' : 'Regular' }}
                                        </span>
                                    </div>
                                    <p class="text-2xs text-faint truncate flex items-center gap-1">
                                        <span class="truncate">{{ $enr->schedule->location->name }}</span>
                                        <x-maps-button :url="$enr->schedule->location->maps_url" />
                                    </p>
                                </div>
                                <p class="text-2xs font-semibold text-muted tabular-nums shrink-0">
                                    {{ \Illuminate\Support\Carbon::parse($enr->schedule->start_time)->format('H:i') }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- The action a parent most often wants from a date is bound to
                     that date. Before this they had to close the calendar, open
                     Leave Requests and pick the day again.

                     Only shown inside the window the form accepts — leave here is
                     reported for a session that already happened (today back to
                     seven days), so offering it on a future date would hand the
                     parent a form that rejects them. --}}
                @if (\App\Livewire\Portal\LeaveRequests::isRequestableDate($selectedDate))
                <a href="{{ route('parent.leaves', ['date' => $selectedDate]) }}"
                   class="mt-4 flex items-center justify-center gap-2 rounded-xl border border-line px-3 py-2.5
                          text-xs font-semibold text-navy hover:bg-off hover:border-navy/30 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-3-3v6m8-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('messages.portal.home.request_leave_on') }}
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
