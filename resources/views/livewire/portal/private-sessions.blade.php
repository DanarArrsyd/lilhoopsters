<div class="flex min-h-[100dvh]">

    {{-- ── Sidebar ── --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-50 w-60 bg-surface border-r border-line flex flex-col
                  transform -translate-x-full lg:translate-x-0 transition-transform duration-300">

        <div class="h-16 flex items-center gap-3 px-4 border-b border-line">
            <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-9 h-9 rounded-xl object-cover shrink-0">
            <div>
                <p class="text-navy font-extrabold text-sm uppercase tracking-tight leading-tight">Lil' Hoopsters</p>
                <p class="text-faint text-[10px] uppercase tracking-wide">Parent Portal</p>
            </div>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            <x-parent-nav activeRoute="parent.private" />
        </nav>

        <div class="border-t border-line p-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-navy rounded-full flex items-center justify-center text-off font-bold text-sm shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-ink text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                    <p class="text-muted text-xs truncate">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-muted hover:text-[#B91C1C] transition-colors p-1" title="Sign out">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-navy/40 z-40 hidden lg:hidden" onclick="closeSidebar()"></div>

    {{-- ── Main ── --}}
    <div class="flex-1 flex flex-col min-w-0 lg:ml-60">

        {{-- Topbar --}}
        <header class="h-14 bg-surface border-b border-line flex items-center px-4 gap-3 sticky top-0 z-30">
            <button class="lg:hidden p-2 rounded-lg hover:bg-off text-muted" onclick="openSidebar()">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="flex-1 text-sm font-bold uppercase tracking-wide text-navy">Private Sessions</h1>
            <span class="text-[11px] text-gray-400 font-medium tabular-nums">{{ $step }} / 5</span>
        </header>

        {{-- Progress bar --}}
        <div class="h-[3px] bg-gray-100 sticky top-14 z-20 shrink-0">
            <div class="h-full bg-navy transition-all duration-500" style="width: {{ round($step / 5 * 100) }}%"></div>
        </div>

        {{-- Wizard content --}}
        <div class="flex-1 bg-white">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-6 pb-20">

                {{-- Flash --}}
                @if (session('success'))
                    <div class="mb-5 flex items-start gap-2.5 px-3.5 py-2.5 bg-green-50 rounded-xl border border-green-100">
                        <svg class="w-3.5 h-3.5 text-green-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <p class="text-xs text-green-700">{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-5 flex items-start gap-2.5 px-3.5 py-2.5 bg-red-50 rounded-xl border border-red-100">
                        <svg class="w-3.5 h-3.5 text-red-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-red-600">{{ session('error') }}</p>
                    </div>
                @endif

                {{-- ── Stepper ── --}}
                @php
                    $stepLabels = [
                        1 => $selectedChild ? \Illuminate\Support\Str::limit($selectedChild->name, 10) : 'Player',
                        2 => $selectedLocation ? \Illuminate\Support\Str::limit($selectedLocation->name, 10) : 'Location',
                        3 => $selectedCoach ? \Illuminate\Support\Str::limit($selectedCoach->user?->name ?? 'Coach', 10) : 'Coach',
                        4 => $selectedPackage ? \Illuminate\Support\Str::limit($selectedPackage->name, 10) : ($selectedDay ? ucfirst($selectedDay) : 'Schedule'),
                        5 => 'Confirm',
                    ];
                @endphp
                <div class="flex items-start mb-5 sm:mb-8">
                    @foreach ($stepLabels as $s => $label)
                        <div class="flex flex-col items-center shrink-0 w-10 sm:w-14">
                            <div @class([
                                'w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center shrink-0 text-[10px] sm:text-xs font-bold',
                                'bg-[#15803D] text-white'               => $step > $s,
                                'bg-navy text-white ring-4 ring-navy/15' => $step === $s,
                                'bg-gray-100 text-gray-400'             => $step < $s,
                            ])>
                                @if ($step > $s)
                                    <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $s }}
                                @endif
                            </div>
                            <p @class([
                                'text-[9px] sm:text-[10px] mt-1 sm:mt-1.5 text-center leading-tight font-medium hidden sm:block',
                                'text-[#15803D]'       => $step > $s,
                                'text-navy font-bold'  => $step === $s,
                                'text-gray-300'        => $step < $s,
                            ])>{{ $label }}</p>
                        </div>
                        @if (!$loop->last)
                            <div class="flex-1 h-px mt-3 sm:mt-3.5 mx-0.5 sm:mx-1 {{ $step > $s ? 'bg-[#15803D]/40' : 'bg-gray-200' }}"></div>
                        @endif
                    @endforeach
                </div>

                {{-- ════════ STEP 1: Player ════════ --}}
                @if ($step === 1)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Who's training?
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Choose the player for this private session.</p>

                    @if ($children->isEmpty())
                        <div class="py-10 text-center border-2 border-dashed border-gray-100 rounded-2xl">
                            <p class="text-sm font-semibold text-gray-500 mb-1">No active players found</p>
                            <p class="text-xs text-gray-400">Add a player to your account before booking.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ($children as $child)
                                <button wire:click="selectChild({{ $child->id }})" wire:loading.attr="disabled"
                                        class="group block text-left w-full h-full">
                                    <div @class([
                                        'bg-white border-2 rounded-xl p-3.5 flex items-center gap-3 transition-all duration-200 h-full',
                                        'border-navy bg-navy/[0.03] shadow-sm'              => $selectedChildId == $child->id,
                                        'border-gray-100 hover:border-navy/40 hover:shadow-sm' => $selectedChildId != $child->id,
                                    ])>
                                        <div @class([
                                            'w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base shrink-0 transition-colors duration-200',
                                            'bg-navy text-white'                                           => $selectedChildId == $child->id,
                                            'bg-navy/8 text-navy group-hover:bg-navy group-hover:text-white' => $selectedChildId != $child->id,
                                        ])>
                                            {{ strtoupper(substr($child->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm truncate">{{ $child->name }}</p>
                                            @if ($child->jersey_number)
                                                <p class="text-[11px] text-gray-400 mt-0.5">#{{ $child->jersey_number }}</p>
                                            @endif
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-navy group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                {{-- ════════ STEP 2: Location ════════ --}}
                @elseif ($step === 2)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Where would you like to train?
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Choose a court to see available private session slots.</p>

                    @if ($locations->isEmpty())
                        <div class="py-10 text-center border-2 border-dashed border-gray-100 rounded-2xl">
                            <p class="text-sm font-semibold text-gray-500 mb-1">No private sessions available</p>
                            <p class="text-xs text-gray-400">There are no active private session slots at any location.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ($locations as $loc)
                                <button wire:click="selectLocation({{ $loc->id }})" wire:loading.attr="disabled"
                                        class="group block text-left w-full h-full">
                                    <div class="bg-white border-2 border-gray-100 rounded-xl p-3.5 flex items-center gap-3 transition-all duration-200 hover:border-navy/40 hover:shadow-sm h-full">
                                        <div class="w-10 h-10 rounded-xl bg-navy/8 text-navy flex items-center justify-center shrink-0 group-hover:bg-navy group-hover:text-white transition-colors duration-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm truncate">{{ $loc->name }}</p>
                                            @if ($loc->address)
                                                <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $loc->address }}</p>
                                            @endif
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-navy group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                {{-- ════════ STEP 3: Coach ════════ --}}
                @elseif ($step === 3)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Pick a coach.
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">
                        {{ $selectedLocation?->name }} — choose who'll run the session.
                    </p>

                    @if ($coaches->isEmpty())
                        <div class="py-8 text-center border-2 border-dashed border-gray-100 rounded-xl">
                            <p class="text-sm text-gray-400">No coaches run private sessions at this location.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ($coaches as $coach)
                                <button wire:click="selectCoach({{ $coach->id }})" wire:loading.attr="disabled"
                                        class="group block text-left w-full h-full">
                                    <div @class([
                                        'bg-white border-2 rounded-xl p-3.5 flex items-center gap-3 transition-all duration-200 h-full',
                                        'border-navy bg-navy/[0.03] shadow-sm'              => $selectedCoachId == $coach->id,
                                        'border-gray-100 hover:border-navy/40 hover:shadow-sm' => $selectedCoachId != $coach->id,
                                    ])>
                                        <div @class([
                                            'w-10 h-10 rounded-xl flex items-center justify-center font-bold text-base shrink-0 transition-colors duration-200',
                                            'bg-navy text-white'                                           => $selectedCoachId == $coach->id,
                                            'bg-navy/8 text-navy group-hover:bg-navy group-hover:text-white' => $selectedCoachId != $coach->id,
                                        ])>
                                            {{ strtoupper(substr($coach->user?->name ?? 'C', 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm truncate">{{ $coach->user?->name ?? 'Coach' }}</p>
                                            @if ($coach->specialization)
                                                <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $coach->specialization }}</p>
                                            @endif
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-navy group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                {{-- ════════ STEP 4a: Pick a day ════════ --}}
                @elseif ($step === 4 && !$selectedDay)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Pick a day.
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">
                        {{ $selectedCoach?->user?->name ?? 'Coach' }} · {{ $selectedLocation?->name }}
                    </p>

                    @if ($availableDays->isEmpty())
                        <div class="py-10 text-center border-2 border-dashed border-gray-100 rounded-2xl">
                            <p class="text-sm font-semibold text-gray-500 mb-1">No slots available</p>
                            <p class="text-xs text-gray-400">This coach has no private slots at this location.</p>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ($availableDays as $day)
                                @php
                                    $daySlots  = $scheduleSlots->filter(fn($s) => $s['schedule']->day_of_week === $day);
                                    $availCnt  = $daySlots->where('available', true)->count();
                                    $totalCnt  = $daySlots->count();
                                @endphp
                                <button wire:click="selectDay('{{ $day }}')" wire:loading.attr="disabled"
                                        class="group block text-left w-full">
                                    <div class="bg-white border-2 border-gray-100 rounded-xl p-4 flex items-center gap-3 transition-all duration-200 hover:border-navy/40 hover:shadow-sm">
                                        <div class="w-10 h-10 rounded-xl bg-navy/8 text-navy flex items-center justify-center shrink-0 group-hover:bg-navy group-hover:text-white transition-colors duration-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm">{{ ucfirst($day) }}</p>
                                            <p class="text-[11px] text-gray-400 mt-0.5">
                                                {{ $totalCnt }} slot{{ $totalCnt !== 1 ? 's' : '' }}
                                                @if ($availCnt > 0)
                                                    · <span class="text-green-600 font-semibold">{{ $availCnt }} open</span>
                                                @else
                                                    · <span class="text-red-400 font-semibold">Full</span>
                                                @endif
                                            </p>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-navy group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                {{-- ════════ STEP 4b: Pick a time slot + package ════════ --}}
                @elseif ($step === 4 && $selectedDay)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Pick a time slot.
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">
                        {{ ucfirst($selectedDay) }} · {{ $selectedCoach?->user?->name ?? 'Coach' }} · {{ $selectedLocation?->name }}
                    </p>

                    {{-- Time slots for selected day --}}
                    <div class="mb-6">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2.5">Available times</p>
                        @if ($timeSlotsForDay->isEmpty())
                            <div class="py-7 text-center border-2 border-dashed border-gray-100 rounded-xl">
                                <p class="text-sm text-gray-400">No slots on {{ ucfirst($selectedDay) }}.</p>
                            </div>
                        @else
                            <div class="space-y-2.5">
                                @foreach ($timeSlotsForDay as $slot)
                                    @php $s = $slot['schedule']; $sel = $selectedScheduleId === $s->id; @endphp
                                    <button
                                        @if ($slot['available']) wire:click="selectSchedule({{ $s->id }})" wire:loading.attr="disabled" @endif
                                        class="group block text-left w-full {{ !$slot['available'] ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                        <div @class([
                                            'bg-white border-2 rounded-xl p-4 transition-all duration-200',
                                            'border-navy bg-navy/[0.03] shadow-sm'                 => $slot['available'] && $sel,
                                            'border-gray-100 hover:border-navy/40 hover:shadow-sm' => $slot['available'] && !$sel,
                                            'border-gray-100'                                      => !$slot['available'],
                                        ])>
                                            <div class="flex items-start justify-between gap-2 mb-2">
                                                <div class="min-w-0">
                                                    <p class="font-bold text-gray-900 text-sm truncate">
                                                        {{ substr($s->start_time, 0, 5) }}–{{ substr($s->end_time, 0, 5) }}
                                                    </p>
                                                </div>
                                                @if (!$slot['available'])
                                                    <span class="shrink-0 text-[9px] bg-red-50 text-red-500 px-2 py-1 rounded-lg font-bold uppercase">Full</span>
                                                @elseif ($sel)
                                                    <span class="shrink-0">
                                                        <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                        </svg>
                                                    </span>
                                                @else
                                                    <span class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                        <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                        </svg>
                                                    </span>
                                                @endif
                                            </div>
                                            <div>
                                                <div class="flex items-center justify-between text-[10px] text-gray-400 mb-1">
                                                    <span>Capacity</span>
                                                    @php $pct = $s->max_capacity > 0 ? min(100, ($slot['enrolled'] / $s->max_capacity) * 100) : 0; @endphp
                                                    <span class="{{ !$slot['available'] ? 'text-red-500 font-bold' : '' }}">
                                                        {{ $slot['enrolled'] }}/{{ $s->max_capacity }}
                                                        {{ $slot['remaining'] > 0 ? '· ' . $slot['remaining'] . ' left' : '· Full' }}
                                                    </span>
                                                </div>
                                                <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full transition-all duration-500
                                                        {{ $pct >= 90 ? 'bg-red-400' : ($pct >= 70 ? 'bg-amber-400' : 'bg-green-400') }}"
                                                         style="width: {{ $pct }}%"></div>
                                                </div>
                                            </div>
                                            <p class="text-[10px] text-gray-400 mt-2">
                                                Next session: <strong class="text-gray-600">{{ $slot['nextOcc']->format('d M Y') }}</strong>
                                                · Book by {{ $slot['nextOcc']->subDay()->format('d M') }}
                                            </p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @error('selectedScheduleId')
                            <p class="text-xs text-red-500 mt-2 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    {{-- Select package --}}
                    <div class="mb-6">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2.5">Package</p>
                        @if ($packages->isEmpty())
                            <div class="py-7 text-center border-2 border-dashed border-gray-100 rounded-xl">
                                <p class="text-sm text-gray-400">No private packages available for this location.</p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($packages as $pkg)
                                    @php $sel = $selectedPackageId === $pkg->id; @endphp
                                    <button wire:click="$set('selectedPackageId', {{ $pkg->id }})" class="block w-full text-left group">
                                        <div class="border-2 rounded-xl px-4 py-3 flex items-center gap-3 transition-all duration-200
                                            {{ $sel ? 'border-navy bg-navy/[0.03] shadow-sm' : 'border-gray-100 hover:border-navy/30 hover:shadow-sm' }}">
                                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center shrink-0 transition-colors duration-200
                                                {{ $sel ? 'border-navy bg-navy' : 'border-gray-300 group-hover:border-navy/50' }}">
                                                @if ($sel)
                                                    <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="font-bold text-gray-900 text-sm leading-none mb-0.5">{{ $pkg->name }}</p>
                                                @if ($pkg->session_count)
                                                    <p class="text-[11px] text-gray-400">{{ $pkg->session_count }} session{{ $pkg->session_count !== 1 ? 's' : '' }}</p>
                                                @elseif ($pkg->validity_days)
                                                    <p class="text-[11px] text-gray-400">{{ $pkg->validity_days }} days validity</p>
                                                @endif
                                            </div>
                                            <p class="font-extrabold text-navy text-base shrink-0">
                                                Rp {{ number_format($pkg->price, 0, ',', '.') }}
                                            </p>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                        @endif
                        @error('selectedPackageId')
                            <p class="text-xs text-red-500 mt-2 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button wire:click="confirmDetails" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-navy text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="confirmDetails" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                        <svg wire:loading wire:target="confirmDetails" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span wire:loading.remove wire:target="confirmDetails">Review Booking</span>
                        <span wire:loading wire:target="confirmDetails">Loading...</span>
                    </button>

                {{-- ════════ STEP 5: Confirm ════════ --}}
                @elseif ($step === 5)

                    @php
                        $confirmRows = [
                            ['label' => 'Booking ID', 'value' => $previewTrxCode, 'mono' => true],
                            ['label' => 'Player',     'value' => $selectedChild?->name ?? '—'],
                            ['label' => 'Coach',      'value' => $selectedSchedule?->coach?->user->name ?? '—'],
                            ['label' => 'Location',   'value' => $selectedSchedule?->location->name ?? '—'],
                            ['label' => 'Schedule',   'value' => $selectedSchedule
                                ? ucfirst($selectedSchedule->day_of_week) . ', '
                                  . substr($selectedSchedule->start_time, 0, 5) . '–'
                                  . substr($selectedSchedule->end_time, 0, 5)
                                : '—'],
                            ['label' => 'Package',    'value' => $selectedPackage?->name ?? '—'],
                            ['label' => 'Total',      'value' => $selectedPackage ? 'Rp ' . number_format($selectedPackage->price, 0, ',', '.') : '—', 'highlight' => true],
                        ];
                    @endphp

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Review your booking.
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Check everything before confirming.</p>

                    {{-- Summary card --}}
                    <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm mb-5">
                        <div class="bg-navy px-4 py-3 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-white/10 text-white flex items-center justify-center font-extrabold text-xs shrink-0">
                                {{ strtoupper(substr($selectedChild?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] font-bold uppercase tracking-widest text-white/40">Private Session Booking</p>
                                <p class="text-sm font-extrabold text-white truncate">{{ $selectedChild?->name }}</p>
                            </div>
                            <span class="shrink-0 text-[9px] font-extrabold uppercase tracking-wide px-2 py-1 rounded-full bg-white/15 text-white/80">
                                Private
                            </span>
                        </div>
                        <div class="divide-y divide-gray-50">
                            @foreach ($confirmRows as $row)
                                <div class="flex items-center justify-between gap-3 px-4 py-3
                                    {{ isset($row['highlight']) && $row['highlight'] ? 'bg-navy/[0.03]' : '' }}">
                                    <span class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 shrink-0 w-20">
                                        {{ $row['label'] }}
                                    </span>
                                    <span @class([
                                        'flex-1 text-right leading-snug',
                                        'font-mono text-xs text-gray-500 bg-gray-50 px-2 py-0.5 rounded-md' => isset($row['mono']) && $row['mono'],
                                        'text-lg font-extrabold text-navy' => isset($row['highlight']) && $row['highlight'],
                                        'text-xs font-semibold text-gray-800' => !isset($row['mono']) && !isset($row['highlight']),
                                    ])>{{ $row['value'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex gap-2.5 px-3.5 py-2.5 bg-blue-50 rounded-xl border border-blue-100 mb-6">
                        <svg class="w-3.5 h-3.5 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-600 leading-relaxed">
                            After booking, upload payment proof in the
                            <a href="{{ route('parent.home') }}" class="font-bold underline">Payments</a> page for admin review.
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <div class="min-w-0">
                            <p class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Total</p>
                            <p class="text-lg font-extrabold text-navy leading-none whitespace-nowrap">
                                Rp {{ number_format($selectedPackage?->price ?? 0, 0, ',', '.') }}
                            </p>
                        </div>
                        <button wire:click="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 bg-[#15803D] text-white text-xs font-bold uppercase tracking-widest px-4 py-2.5 rounded-xl hover:bg-[#166534] active:scale-[0.97] transition-all duration-150 disabled:opacity-50 whitespace-nowrap shrink-0">
                            <svg wire:loading.remove wire:target="submit" class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg wire:loading wire:target="submit" class="w-3.5 h-3.5 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            <span wire:loading.remove wire:target="submit">Book Private</span>
                            <span wire:loading wire:target="submit">Processing...</span>
                        </button>
                    </div>

                @endif

            </div>{{-- /max-w-2xl --}}
        </div>{{-- /flex-1 bg-white --}}

        {{-- Back button (fixed bottom-right, step 2+) --}}
        @if ($step > 1)
            <div class="fixed bottom-5 right-4 sm:bottom-6 sm:right-6 z-20">
                <button wire:click="back" wire:loading.attr="disabled"
                    class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:border-navy hover:text-navy shadow-sm transition-colors duration-150">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </button>
            </div>
        @endif

    </div>{{-- /main --}}

</div>{{-- /flex --}}

@push('scripts')
<script>
    function openSidebar() {
        document.getElementById('sidebar').classList.remove('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.remove('hidden');
    }
    function closeSidebar() {
        document.getElementById('sidebar').classList.add('-translate-x-full');
        document.getElementById('sidebar-overlay').classList.add('hidden');
    }
</script>
@endpush
