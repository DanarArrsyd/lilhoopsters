<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Enroll Player</h2>
            <p class="text-sm text-slate-500">Register a new player or join a program</p>
        </div>
        @if ($step > 1)
            <x-btn variant="secondary" wire:click="resetWizard">Start Over</x-btn>
        @endif
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Step indicator --}}
    <div class="flex items-center gap-2 mb-8">
        @foreach ([1 => 'Select Player', 2 => 'Choose Plan', 3 => 'Confirm'] as $s => $label)
            <div class="flex items-center gap-2 {{ !$loop->last ? 'flex-1' : '' }}">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0
                    {{ $step > $s ? 'bg-green-500 text-white' : ($step === $s ? 'bg-orange-500 text-white' : 'bg-slate-200 text-slate-500') }}">
                    @if ($step > $s)
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    @else
                        {{ $s }}
                    @endif
                </div>
                <span class="text-xs {{ $step === $s ? 'font-semibold text-slate-900' : 'text-slate-400' }} hidden sm:inline">
                    {{ $label }}
                </span>
                @if (!$loop->last)
                    <div class="flex-1 h-px bg-slate-200 mx-2"></div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- ── STEP 1: Select player ── --}}
    @if ($step === 1)
        @if ($enrollableChildren->isEmpty())
            <x-card padding="p-8">
                <x-empty-state
                    title="No players available to enroll"
                    description="Add a player in My Players, or all your players are already pending approval." />
                <div class="text-center mt-4">
                    <a href="{{ route('parent.players') }}">
                        <x-btn variant="primary">Go to My Players</x-btn>
                    </a>
                </div>
            </x-card>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($enrollableChildren as $child)
                    @php $months = $child->ageInMonths(); @endphp
                    <button wire:click="selectChild({{ $child->id }})"
                            class="text-left w-full">
                        <x-card padding="p-5" class="hover:shadow-md hover:border-orange-200 transition-all cursor-pointer border-2 border-transparent">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold shrink-0
                                    {{ $child->gender === 'male' ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600' }}">
                                    {{ strtoupper(substr($child->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $child->name }}</p>
                                    <p class="text-xs text-slate-500">
                                        @if ($months >= 12)
                                            {{ floor($months / 12) }}yr {{ $months % 12 > 0 ? ($months % 12) . 'mo' : '' }}
                                        @else
                                            {{ $months }}mo
                                        @endif
                                    </p>
                                </div>
                                <div class="ml-auto">
                                    @if ($child->status === 'unregistered')
                                        <span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">Register</span>
                                    @else
                                        <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Enroll</span>
                                    @endif
                                </div>
                            </div>
                        </x-card>
                    </button>
                @endforeach
            </div>
        @endif

    {{-- ── STEP 2: Registration — pick location + package ── --}}
    @elseif ($step === 2 && $enrollmentType === 'registration')
        @php $child = $enrollableChildren->find($selectedChildId); @endphp
        <div class="mb-4">
            <p class="text-sm text-slate-500">Registering: <span class="font-semibold text-slate-800">{{ $child?->name }}</span></p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Location picker --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Select Location</h3>
                <div class="space-y-2">
                    @foreach ($locations as $loc)
                        <button wire:click="selectLocation({{ $loc->id }})"
                                class="w-full text-left p-3 rounded-xl border-2 transition-all
                                    {{ $selectedLocationId === $loc->id
                                        ? 'border-orange-400 bg-orange-50'
                                        : 'border-slate-200 hover:border-slate-300' }}">
                            <p class="font-medium text-slate-900 text-sm">{{ $loc->name }}</p>
                            <p class="text-xs text-slate-500 mt-0.5">{{ $loc->address }}</p>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Package picker --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Registration Package</h3>
                @if (!$selectedLocationId)
                    <p class="text-sm text-slate-400 italic">Select a location to see packages</p>
                @elseif ($registrationPackages->isEmpty())
                    <p class="text-sm text-slate-400 italic">No registration packages available at this location</p>
                @else
                    <div class="space-y-2">
                        @foreach ($registrationPackages as $pkg)
                            <button wire:click="$set('selectedPackageId', {{ $pkg->id }})"
                                    class="w-full text-left p-4 rounded-xl border-2 transition-all
                                        {{ $selectedPackageId === $pkg->id
                                            ? 'border-orange-400 bg-orange-50'
                                            : 'border-slate-200 hover:border-slate-300' }}">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900 text-sm">{{ $pkg->name }}</p>
                                        @if ($pkg->description)
                                            <p class="text-xs text-slate-500 mt-0.5">{{ $pkg->description }}</p>
                                        @endif
                                    </div>
                                    <p class="text-orange-600 font-bold text-sm shrink-0 ml-3">{{ $pkg->formattedPrice() }}</p>
                                </div>
                                @if ($pkg->is_popular)
                                    <span class="inline-block mt-2 text-[10px] bg-orange-100 text-orange-600 px-2 py-0.5 rounded-full font-semibold uppercase tracking-wide">Popular</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @error('selectedLocationId') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
        @error('selectedPackageId') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror

        <div class="flex gap-3 mt-6">
            <x-btn variant="secondary" wire:click="back">Back</x-btn>
            <x-btn variant="primary" wire:click="goToStep3Registration"
                   wire:loading.attr="disabled">Continue</x-btn>
        </div>

    {{-- ── STEP 2: Program — schedule browser ── --}}
    @elseif ($step === 2 && $enrollmentType === 'program')
        @php $child = $enrollableChildren->find($selectedChildId); @endphp
        <div class="mb-4">
            <p class="text-sm text-slate-500">Enrolling: <span class="font-semibold text-slate-800">{{ $child?->name }}</span></p>
        </div>

        {{-- Filters --}}
        <x-card class="mb-4" padding="p-4">
            <div class="flex gap-3 flex-wrap">
                <div class="w-44">
                    <select wire:model.live="filterLocationId"
                            class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                        <option value="">All Locations</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <select wire:model.live="filterDay"
                            class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                        <option value="">All Days</option>
                        @foreach (['monday','tuesday','wednesday','thursday','friday','saturday','sunday'] as $day)
                            <option value="{{ $day }}">{{ ucfirst($day) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </x-card>

        @if ($schedules->isEmpty())
            <x-empty-state title="No schedules found" description="Try adjusting your filters." />
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($schedules as $sched)
                    <button wire:click="selectSchedule({{ $sched->id }})" class="text-left w-full">
                        <x-card padding="p-4" class="hover:shadow-md hover:border-orange-200 transition-all cursor-pointer border-2 border-transparent">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900 text-sm">{{ $sched->program->name }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $sched->location->name }}</p>
                                </div>
                                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded-lg font-medium shrink-0 ml-3">
                                    {{ ucfirst($sched->day_of_week) }}
                                </span>
                            </div>
                            <div class="flex items-center gap-4 mt-3 text-xs text-slate-500">
                                <span>{{ \Carbon\Carbon::createFromTimeString($sched->start_time)->format('H:i') }} – {{ \Carbon\Carbon::createFromTimeString($sched->end_time)->format('H:i') }}</span>
                                <span>Coach: {{ $sched->coach->user->name }}</span>
                            </div>
                            <div class="mt-2">
                                @php $approved = $sched->approvedEnrollmentsCount(); @endphp
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-orange-400 rounded-full" style="width: {{ min(100, ($approved / $sched->max_capacity) * 100) }}%"></div>
                                    </div>
                                    <span class="text-xs text-slate-400 shrink-0">{{ $approved }}/{{ $sched->max_capacity }}</span>
                                </div>
                            </div>
                        </x-card>
                    </button>
                @endforeach
            </div>
        @endif

        <div class="mt-6">
            <x-btn variant="secondary" wire:click="back">Back</x-btn>
        </div>

    {{-- ── STEP 3: Confirm ── --}}
    @elseif ($step === 3)
        @php
            $child   = $enrollableChildren->find($selectedChildId);
            $schedule = $selectedScheduleId ? \App\Models\Schedule::with(['program','location','coach.user'])->find($selectedScheduleId) : null;
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Summary --}}
            <div>
                <h3 class="text-sm font-semibold text-slate-700 mb-3">Summary</h3>
                <x-card padding="p-5" class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Player</span>
                        <span class="font-medium text-slate-900">{{ $child?->name }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-500">Type</span>
                        <span class="font-medium text-slate-900 capitalize">{{ $enrollmentType }}</span>
                    </div>
                    @if ($schedule)
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Program</span>
                            <span class="font-medium text-slate-900">{{ $schedule->program->name }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Schedule</span>
                            <span class="font-medium text-slate-900">{{ ucfirst($schedule->day_of_week) }}, {{ \Carbon\Carbon::createFromTimeString($schedule->start_time)->format('H:i') }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-slate-500">Location</span>
                            <span class="font-medium text-slate-900">{{ $schedule->location->name }}</span>
                        </div>
                    @endif
                    @if ($selectedPackageId)
                        @php $pkg = $programPackages->find($selectedPackageId) ?? $registrationPackages->find($selectedPackageId); @endphp
                        @if ($pkg)
                            <div class="border-t border-slate-100 pt-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Package</span>
                                    <span class="font-medium text-slate-900">{{ $pkg->name }}</span>
                                </div>
                                <div class="flex justify-between text-sm mt-1">
                                    <span class="text-slate-500">Amount</span>
                                    <span class="font-bold text-orange-600">{{ $pkg->formattedPrice() }}</span>
                                </div>
                            </div>
                        @endif
                    @endif
                </x-card>

                <div class="mt-4 p-4 bg-blue-50 rounded-xl border border-blue-100">
                    <p class="text-xs font-semibold text-blue-700">After submitting:</p>
                    <p class="text-xs text-blue-600 mt-1">Upload payment proof in the <a href="{{ route('parent.payments') }}" class="underline">Payments</a> page. Your enrollment will be reviewed by admin.</p>
                </div>
            </div>

            {{-- Package + extras --}}
            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700 mb-3">Select Package</h3>
                    @if ($programPackages->isEmpty() && $registrationPackages->isEmpty())
                        <p class="text-sm text-slate-400 italic">No packages available</p>
                    @else
                        @php $packages = $enrollmentType === 'registration' ? $registrationPackages : $programPackages; @endphp
                        <div class="space-y-2">
                            @foreach ($packages as $pkg)
                                <button wire:click="$set('selectedPackageId', {{ $pkg->id }})"
                                        class="w-full text-left p-4 rounded-xl border-2 transition-all
                                            {{ $selectedPackageId === $pkg->id
                                                ? 'border-orange-400 bg-orange-50'
                                                : 'border-slate-200 hover:border-slate-300' }}">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900 text-sm">{{ $pkg->name }}</p>
                                            @if ($pkg->session_count)
                                                <p class="text-xs text-slate-500">{{ $pkg->session_count }} sessions</p>
                                            @elseif ($pkg->period_end)
                                                <p class="text-xs text-slate-500">Until {{ $pkg->period_end->format('d M Y') }}</p>
                                            @elseif ($pkg->validity_days)
                                                <p class="text-xs text-slate-500">{{ $pkg->validity_days }} days</p>
                                            @endif
                                        </div>
                                        <p class="text-orange-600 font-bold text-sm shrink-0 ml-3">{{ $pkg->formattedPrice() }}</p>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @error('selectedPackageId') <p class="text-xs text-red-500 mt-2">{{ $message }}</p> @enderror
                </div>

                @if ($enrollmentType === 'registration')
                    <div class="border-t border-slate-100 pt-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Jersey Info (optional)</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-slate-700">Jersey Name</label>
                                <x-input wire:model="jerseyName" placeholder="e.g. BUDI" />
                            </div>
                            <div class="space-y-1">
                                <label class="block text-sm font-medium text-slate-700">Number</label>
                                <x-input wire:model="jerseyNumber" placeholder="e.g. 23" />
                            </div>
                        </div>
                    </div>
                @endif

                <div class="space-y-1">
                    <label class="block text-sm font-medium text-slate-700">Notes (optional)</label>
                    <textarea wire:model="memberNotes" rows="2"
                              class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                              placeholder="Any additional info for the admin..."></textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-6">
            <x-btn variant="secondary" wire:click="back">Back</x-btn>
            <x-btn variant="primary" wire:click="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Submit Enrollment</span>
                <span wire:loading wire:target="submit">Submitting...</span>
            </x-btn>
        </div>
    @endif
</div>
