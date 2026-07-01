<div class="flex flex-col min-h-[100dvh]">

    {{-- Topbar --}}
    <header class="h-14 bg-surface border-b border-line flex justify-between lg:grid lg:grid-cols-[1fr_auto_1fr] items-center px-4 gap-4 sticky top-0 z-30">
        <div class="flex items-center min-w-0">
            <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
        </div>
        <x-portal.top-nav />
        <div class="flex items-center justify-end gap-4">
            <span class="text-[11px] text-gray-400 font-medium tabular-nums shrink-0">{{ $step }} / {{ $totalSteps }}</span>
            <livewire:locale-switcher />
            <livewire:notification-bell />
            <x-portal.avatar-menu />
        </div>
    </header>

        {{-- Progress bar (sits flush below topbar) --}}
        <div class="h-[3px] bg-gray-100 sticky top-14 z-20 shrink-0">
            <div class="h-full bg-navy transition-all duration-500" style="width: {{ round($step / $totalSteps * 100) }}%"></div>
        </div>

        {{-- ── Scrollable wizard content ── --}}
        <div class="flex-1 bg-white">
            <div class="max-w-2xl mx-auto px-4 sm:px-6 pt-6 pb-24 lg:pb-20">

                {{-- ── Stepper ── --}}
                @php
                    $stepperChild    = $enrollableChildren->firstWhere('id', $selectedChildId);
                    $stepperLocation = $selectedLocationId ? $availableLocations->firstWhere('id', $selectedLocationId) ?? \App\Models\Location::find($selectedLocationId) : null;
                    $stepLabels = [
                        1 => $stepperChild ? \Illuminate\Support\Str::limit($stepperChild->name, 10) : 'Player',
                        2 => $selectedDay && $stepperLocation
                                ? ucfirst(substr($selectedDay, 0, 3)) . ' · ' . \Illuminate\Support\Str::limit($stepperLocation->name, 8)
                                : 'Day & Field',
                        3 => $selectedSchedule
                                ? \Illuminate\Support\Str::limit($selectedSchedule->program->name, 10) . "\n"
                                  . \Carbon\Carbon::createFromTimeString($selectedSchedule->start_time)->format('H:i')
                                  . '–' . \Carbon\Carbon::createFromTimeString($selectedSchedule->end_time)->format('H:i')
                                : 'Session',
                        4 => $selectedPackageId && $availablePackages->firstWhere('id', $selectedPackageId)
                                ? \Illuminate\Support\Str::limit($availablePackages->firstWhere('id', $selectedPackageId)->name, 10)
                                : 'Details',
                        5 => 'Confirm',
                    ];
                @endphp
                <div class="flex items-start mb-5 sm:mb-8">
                    @foreach ($stepLabels as $s => $label)
                        <div class="flex flex-col items-center shrink-0 w-10 sm:w-14">
                            <div @class([
                                'w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center shrink-0 text-[10px] sm:text-xs font-bold',
                                'bg-[#15803D] text-white'              => $step > $s,
                                'bg-navy text-white ring-4 ring-navy/15' => $step === $s,
                                'bg-gray-100 text-gray-400'            => $step < $s,
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
                                'text-[9px] sm:text-[10px] mt-1 sm:mt-1.5 text-center leading-tight font-medium whitespace-pre-line hidden sm:block',
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


                {{-- ════════ STEP 1: Select Player ════════ --}}
                @if ($step === 1)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Who are you enrolling?
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Select a player to register or enroll in a program.</p>

                    @if ($enrollableChildren->isEmpty())
                        <div class="py-10 text-center border-2 border-dashed border-gray-100 rounded-2xl">
                            <p class="text-sm font-semibold text-gray-500 mb-1">No players available</p>
                            <p class="text-xs text-gray-400 mb-5">Add a player first, or all your players are pending approval.</p>
                            <a href="{{ route('parent.home') }}"
                                class="inline-flex items-center gap-2 bg-navy text-white text-xs font-bold uppercase tracking-wide px-4 py-2 rounded-xl hover:bg-navy/90 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                                Go to My Players
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            @foreach ($enrollableChildren as $child)
                                @php $months = $child->ageInMonths(); @endphp
                                <button wire:click="selectChild({{ $child->id }})" wire:loading.attr="disabled"
                                        class="group block text-left w-full h-full">
                                    <div class="bg-white border-2 border-gray-100 rounded-xl p-3.5 flex items-center gap-3 transition-all duration-200 hover:border-navy/40 hover:shadow-sm h-full">
                                        <div class="w-10 h-10 rounded-xl bg-navy/8 text-navy flex items-center justify-center font-bold text-base shrink-0 group-hover:bg-navy group-hover:text-white transition-colors duration-200">
                                            {{ strtoupper(substr($child->name, 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-gray-900 text-sm truncate">{{ $child->name }}</p>
                                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                <span class="text-[11px] text-gray-400">
                                                    @if ($months >= 12)
                                                        {{ floor($months / 12) }}yr{{ $months % 12 > 0 ? ' ' . ($months % 12) . 'mo' : '' }}
                                                    @else
                                                        {{ $months }}mo
                                                    @endif
                                                </span>
                                                @if ($child->status === 'unregistered')
                                                    <span class="text-[9px] bg-amber-50 text-amber-600 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wide">Register</span>
                                                @else
                                                    <span class="text-[9px] bg-green-50 text-green-600 px-1.5 py-0.5 rounded-full font-bold uppercase tracking-wide">Active</span>
                                                @endif
                                            </div>
                                        </div>
                                        <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-navy group-hover:translate-x-0.5 transition-all duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif


                {{-- ════════ STEP 2: Day + Field ════════ --}}
                @elseif ($step === 2)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        When and where?
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Pick a training day, then choose a field.</p>

                    @if ($availableDays->isEmpty())
                        <p class="text-sm text-gray-400 italic">No active training days found.</p>
                    @else
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2.5">Training Day</p>
                        <div class="flex items-center gap-2 flex-wrap mb-6">
                            @foreach ($availableDays as $day)
                                <button wire:click="selectDay('{{ $day }}')"
                                        class="px-4 py-2 rounded-full text-xs font-bold uppercase tracking-wide transition-all duration-200
                                            {{ $selectedDay === $day ? 'bg-navy text-white shadow-sm' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                                    {{ ucfirst($day) }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($selectedDay)
                        <div wire:key="locations-{{ $selectedDay }}">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2.5">
                                Fields on {{ ucfirst($selectedDay) }}
                            </p>
                            @if ($availableLocations->isEmpty())
                                <div class="py-7 text-center border-2 border-dashed border-gray-100 rounded-xl">
                                    <p class="text-sm text-gray-400">No fields on {{ ucfirst($selectedDay) }}</p>
                                </div>
                            @else
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                    @foreach ($availableLocations as $loc)
                                        <button wire:click="selectLocation({{ $loc->id }})" wire:loading.attr="disabled"
                                                class="group block text-left w-full h-full">
                                            <div class="bg-white border-2 border-gray-100 rounded-xl p-3.5 flex items-center gap-3 transition-all duration-200 hover:border-navy/40 hover:shadow-sm h-full">
                                                <div class="w-9 h-9 rounded-xl bg-navy/8 text-navy flex items-center justify-center shrink-0 group-hover:bg-navy group-hover:text-white transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    </svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="font-bold text-gray-900 text-sm truncate">{{ $loc->name }}</p>
                                                    @if ($loc->address)
                                                        <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $loc->address }}</p>
                                                    @endif
                                                </div>
                                                <svg class="w-3.5 h-3.5 text-gray-300 group-hover:text-navy transition-all duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif


                {{-- ════════ STEP 3: Program & Time ════════ --}}
                @elseif ($step === 3)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Choose a session.
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Tap a session to select it.</p>

                    @if ($availableSchedules->isEmpty())
                        <div class="py-8 text-center border-2 border-dashed border-gray-100 rounded-xl">
                            <p class="text-sm text-gray-400">No sessions available for this day and field.</p>
                        </div>
                    @else
                        <div class="space-y-2.5">
                            @foreach ($availableSchedules as $sched)
                                @php
                                    $approved = $sched->approvedEnrollmentsCount();
                                    $pct      = $sched->max_capacity > 0 ? min(100, ($approved / $sched->max_capacity) * 100) : 0;
                                    $full     = $approved >= $sched->max_capacity;
                                @endphp
                                <button
                                    @if (!$full) wire:click="selectSchedule({{ $sched->id }})" wire:loading.attr="disabled" @endif
                                    class="group block text-left w-full {{ $full ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer' }}">
                                    <div class="bg-white border-2 rounded-xl p-4 transition-all duration-200 {{ !$full ? 'border-gray-100 hover:border-navy/40 hover:shadow-sm' : 'border-gray-100' }}">
                                        <div class="flex items-start justify-between gap-2 mb-2">
                                            <div class="min-w-0">
                                                <p class="font-bold text-gray-900 text-sm truncate">{{ $sched->program->name }}</p>
                                                <p class="text-[11px] text-gray-400 mt-0.5 truncate">
                                                    @if ($sched->coach)
                                                        Coach · {{ $sched->coach->user->name }}
                                                    @else
                                                        Regular session
                                                    @endif
                                                </p>
                                            </div>
                                            @if ($full)
                                                <span class="shrink-0 text-[9px] bg-red-50 text-red-500 px-2 py-1 rounded-lg font-bold uppercase">Full</span>
                                            @else
                                                <span class="shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                    <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-2.5">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ \Carbon\Carbon::createFromTimeString($sched->start_time)->format('H:i') }} – {{ \Carbon\Carbon::createFromTimeString($sched->end_time)->format('H:i') }}
                                        </div>
                                        <div>
                                            <div class="flex items-center justify-between text-[10px] text-gray-400 mb-1">
                                                <span>Capacity</span>
                                                <span class="{{ $full ? 'text-red-500 font-bold' : '' }}">{{ $approved }}/{{ $sched->max_capacity }}{{ $full ? ' · Full' : '' }}</span>
                                            </div>
                                            <div class="h-1 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full transition-all duration-500 {{ $pct >= 90 ? 'bg-red-400' : ($pct >= 70 ? 'bg-amber-400' : 'bg-green-400') }}"
                                                     style="width: {{ $pct }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif


                {{-- ════════ STEP 4: Package + Details ════════ --}}
                @elseif ($step === 4)

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Pick a package.
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Choose your billing plan and fill in any extra details.</p>

                    {{-- Package selection --}}
                    <div class="mb-6">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2.5">Package</p>
                        @if ($availablePackages->isEmpty())
                            <div class="py-7 text-center border-2 border-dashed border-gray-100 rounded-xl">
                                <p class="text-sm text-gray-400">No packages available at this location.</p>
                            </div>
                        @else
                            <div class="space-y-2">
                                @foreach ($availablePackages as $pkg)
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
                                                    <p class="text-[11px] text-gray-400">{{ $pkg->session_count }} sessions</p>
                                                @elseif (isset($pkg->validity_days) && $pkg->validity_days)
                                                    <p class="text-[11px] text-gray-400">{{ $pkg->validity_days }} days validity</p>
                                                @elseif (isset($pkg->period_end) && $pkg->period_end)
                                                    <p class="text-[11px] text-gray-400">Until {{ $pkg->period_end->format('d M Y') }}</p>
                                                @endif
                                            </div>
                                            <p class="font-extrabold text-navy text-base shrink-0">{{ $pkg->formattedPrice() }}</p>
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

                    {{-- Start date picker (regular packages only) --}}
                    @php
                        $selectedPkg = $selectedPackageId ? $availablePackages->firstWhere('id', $selectedPackageId) : null;
                    @endphp
                    @if ($selectedPkg?->type === 'regular')
                    <div class="mb-6">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Training Start Date
                            <span class="text-red-400 ml-0.5">*</span>
                        </p>
                        @if ($selectedPkg->validity_days)
                            <p class="text-[11px] text-gray-400 mb-3">
                                Package valid for {{ $selectedPkg->validity_days }} days from your chosen start date.
                                @if ($startDate)
                                    Expires on <strong class="text-navy">{{ \Carbon\Carbon::parse($startDate)->addDays($selectedPkg->validity_days - 1)->format('d M Y') }}</strong>.
                                @endif
                            </p>
                        @else
                            <p class="text-[11px] text-gray-400 mb-3">Choose when you'd like to start training.</p>
                        @endif
                        <input wire:model.live="startDate" type="date"
                               min="{{ now()->toDateString() }}"
                               class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-base text-navy py-1.5 focus:outline-none transition-colors caret-navy">
                        @error('startDate')
                            <p class="text-xs text-red-500 mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    @endif

                    {{-- Jersey (registration only) --}}
                    @if ($enrollmentType === 'registration')
                        <div class="mb-6">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Jersey
                                <span class="text-gray-300 font-normal normal-case tracking-normal ml-1">— optional</span>
                            </p>
                            <p class="text-[11px] text-gray-400 mb-3">Printed on your child's jersey.</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Name</label>
                                    <input wire:model="jerseyName" type="text" placeholder="e.g. BUDI"
                                        class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-base text-navy py-1.5 focus:outline-none placeholder:text-gray-300 transition-colors caret-navy uppercase">
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold uppercase tracking-widest text-gray-400 mb-1.5">Number</label>
                                    <input wire:model="jerseyNumber" type="text" placeholder="e.g. 23"
                                        class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-base text-navy py-1.5 focus:outline-none placeholder:text-gray-300 transition-colors caret-navy">
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Notes --}}
                    <div class="mb-6">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-0.5">Notes
                            <span class="text-gray-300 font-normal normal-case tracking-normal ml-1">— optional</span>
                        </p>
                        <p class="text-[11px] text-gray-400 mb-3">Injuries, preferences, anything for the admin.</p>
                        <textarea wire:model="memberNotes" rows="2" placeholder="e.g. Allergic to latex, prefers mornings..."
                            class="w-full border-0 border-b-2 border-gray-200 focus:border-navy bg-transparent text-sm text-navy py-1.5 focus:outline-none placeholder:text-gray-300 transition-colors resize-none caret-navy"></textarea>
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
                        <span wire:loading.remove wire:target="confirmDetails">Review Order</span>
                        <span wire:loading wire:target="confirmDetails">Loading...</span>
                    </button>


                {{-- ════════ STEP 5: Confirmation ════════ --}}
                @elseif ($step === 5)
                    @php
                        $confirmChild    = $enrollableChildren->firstWhere('id', $selectedChildId);
                        $confirmSchedule = $selectedSchedule;
                        $confirmPackage  = $availablePackages->firstWhere('id', $selectedPackageId);
                        $confirmRows = [
                            ['label' => 'ID TRX',   'value' => $previewTrxCode,  'mono' => true],
                            ['label' => 'Player',   'value' => $confirmChild?->name ?? '—'],
                            ['label' => 'Day',      'value' => $selectedDay ? ucfirst($selectedDay) : '—'],
                            ['label' => 'Location', 'value' => $confirmSchedule?->location->name ?? '—'],
                            ['label' => 'Schedule', 'value' => $confirmSchedule
                                ? $confirmSchedule->program->name . ' ('
                                  . \Carbon\Carbon::createFromTimeString($confirmSchedule->start_time)->format('H:i')
                                  . ' – '
                                  . \Carbon\Carbon::createFromTimeString($confirmSchedule->end_time)->format('H:i') . ')'
                                : '—'],
                            ['label' => 'Package',  'value' => $confirmPackage?->name ?? '—'],
                            ...($confirmPackage?->type === 'regular' && $startDate ? [
                                ['label' => 'Start Date',  'value' => \Carbon\Carbon::parse($startDate)->format('d M Y')],
                                ['label' => 'Expires',     'value' => \Carbon\Carbon::parse($startDate)->addDays(($confirmPackage->validity_days ?? 1) - 1)->format('d M Y')],
                            ] : []),
                            ['label' => 'Price',    'value' => $confirmPackage?->formattedPrice() ?? '—', 'highlight' => true],
                        ];
                    @endphp

                    <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                        Review your order.
                    </h1>
                    <p class="text-xs text-gray-400 mb-5">Check everything before enrolling.</p>

                    {{-- Summary card --}}
                    <div class="border border-gray-100 rounded-2xl overflow-hidden shadow-sm mb-5">
                        <div class="bg-navy px-4 py-3 flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-white/10 text-white flex items-center justify-center font-extrabold text-xs shrink-0">
                                {{ strtoupper(substr($confirmChild?->name ?? '?', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-[9px] font-bold uppercase tracking-widest text-white/40">Enrollment Order</p>
                                <p class="text-sm font-extrabold text-white truncate">{{ $confirmChild?->name }}</p>
                            </div>
                            <span class="shrink-0 text-[9px] font-extrabold uppercase tracking-wide px-2 py-1 rounded-full
                                {{ $enrollmentType === 'registration' ? 'bg-amber-500/25 text-amber-300' : 'bg-green-500/25 text-green-300' }}">
                                {{ ucfirst($enrollmentType) }}
                            </span>
                        </div>
                        <div class="divide-y divide-gray-50">
                            @foreach ($confirmRows as $row)
                                <div class="flex items-center justify-between gap-3 px-4 py-3
                                    {{ isset($row['highlight']) && $row['highlight'] ? 'bg-navy/[0.03]' : '' }}">
                                    <span class="text-[10px] font-semibold uppercase tracking-[0.1em] text-gray-400 shrink-0 w-16">
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

                    @if ($enrollmentType === 'registration' && ($jerseyName || $jerseyNumber))
                        <div class="flex items-center gap-2.5 px-3.5 py-2.5 bg-gray-50 rounded-xl border border-gray-100 mb-3">
                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="text-xs text-gray-500">
                                Jersey:
                                @if ($jerseyName) <span class="font-bold text-gray-700">{{ strtoupper($jerseyName) }}</span> @endif
                                @if ($jerseyNumber) <span class="text-gray-400"> · #{{ $jerseyNumber }}</span> @endif
                            </p>
                        </div>
                    @endif

                    @if ($memberNotes)
                        <div class="flex items-start gap-2.5 px-3.5 py-2.5 bg-gray-50 rounded-xl border border-gray-100 mb-3">
                            <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                            </svg>
                            <p class="text-xs text-gray-500 leading-relaxed">{{ $memberNotes }}</p>
                        </div>
                    @endif

                    <div class="flex gap-2.5 px-3.5 py-2.5 bg-blue-50 rounded-xl border border-blue-100 mb-6">
                        <svg class="w-3.5 h-3.5 text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-xs text-blue-600 leading-relaxed">
                            After enrolling, upload payment proof on the
                            <a href="{{ route('parent.home') }}" class="font-bold underline">Home</a> page for admin review.
                        </p>
                    </div>

                    <div class="flex items-center justify-between gap-3 bg-gray-50 rounded-xl px-4 py-3 border border-gray-100">
                        <div>
                            <p class="text-[9px] font-bold uppercase tracking-widest text-gray-400">Total</p>
                            <p class="text-lg font-extrabold text-navy leading-none">{{ $confirmPackage?->formattedPrice() }}</p>
                        </div>
                        <button wire:click="submit" wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 bg-[#15803D] text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl hover:bg-[#166534] active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                            <svg wire:loading.remove wire:target="submit" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg wire:loading wire:target="submit" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            <span wire:loading.remove wire:target="submit">Enroll Now</span>
                            <span wire:loading wire:target="submit">Processing...</span>
                        </button>
                    </div>

                @endif

            </div>{{-- /max-w-2xl --}}
        </div>{{-- /flex-1 bg-white --}}

        {{-- Back arrow (fixed bottom-right, only step 2+) --}}
        @if ($step > 1)
            <div class="fixed bottom-20 right-4 lg:bottom-6 lg:right-6 z-20">
                <button wire:click="back" wire:loading.attr="disabled"
                    class="w-8 h-8 sm:w-9 sm:h-9 flex items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-400 hover:border-navy hover:text-navy shadow-sm transition-colors duration-150">
                    <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </button>
            </div>
        @endif

    <x-portal.bottom-nav />

</div>{{-- /flex --}}
