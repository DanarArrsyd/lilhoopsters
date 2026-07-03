<div x-data="{
    getGps(then) {
        if (!navigator.geolocation) { then(); return; }
        navigator.geolocation.getCurrentPosition(
            pos => {
                $wire.set('latitude',  pos.coords.latitude);
                $wire.set('longitude', pos.coords.longitude);
                then();
            },
            () => then(),
            { timeout: 6000, maximumAge: 60000 }
        );
    }
}" class="max-w-6xl mx-auto">
    <x-admin.page-header :title="__('messages.coach.checkin.title')" :subtitle="now()->format('l, d F Y') . ' · ' . __('messages.coach.checkin.subtitle')" />

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif
    @if (session('warning'))
        <x-alert type="warning" class="mb-4">{{ session('warning') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif
    @if (($autoClosedCount ?? 0) > 0)
        <x-alert type="warning" class="mb-4">{{ __('messages.coach.checkin.auto_closed', ['n' => $autoClosedCount]) }}</x-alert>
    @endif

    {{-- All today's schedules (private + regular) --}}
    @php
        $allSchedules = $privateSchedules->concat($regularSchedules)->sortBy('start_time')->values();
    @endphp

    @if ($allSchedules->isEmpty())
        {{-- No sessions today --}}
        <div class="bg-surface border border-line rounded-2xl p-6 text-center mb-4">
            <p class="text-sm font-semibold text-ink mb-0.5">{{ __('messages.coach.checkin.no_sessions') }}</p>
            <p class="text-xs text-faint">{{ __('messages.coach.checkin.no_schedules', ['day' => now()->format('l')]) }}</p>
        </div>

        {{-- Upcoming --}}
        @if ($upcomingDate && $upcomingSchedules->isNotEmpty())
            <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-2.5">{{ __('messages.coach.checkin.next_session') }}</p>
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">
                <div class="px-4 py-2.5 border-b border-line bg-off">
                    <p class="text-xs font-semibold text-navy">
                        {{ $upcomingDate->format('l, d F Y') }}
                        <span class="text-faint font-normal">· {{ $upcomingDate->diffForHumans() }}</span>
                    </p>
                </div>
                <div class="divide-y divide-line">
                    @foreach ($upcomingSchedules as $sched)
                        <div class="flex items-center gap-3 px-4 py-3">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-ink truncate">{{ $sched->program?->name ?? __('messages.admin.schedules.type_private') }}</p>
                                <p class="text-xs text-faint">{{ $sched->location->name }} · {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}</p>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full shrink-0
                                {{ $sched->type === 'private' ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-600' }}">
                                {{ $sched->type === 'private' ? __('messages.coach.dashboard.private_badge') : __('messages.coach.dashboard.regular_badge') }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    @else
        <div class="bg-surface border border-line rounded-2xl overflow-hidden">
            <div class="divide-y divide-line">
                @foreach ($allSchedules as $sched)
                    @php
                        $checkedIn      = in_array($sched->id, $mySessions);
                        $sessionCoaches = $sessionsBySchedule->get($sched->id, collect());
                        $hasPrimary     = $sessionCoaches->where('role', 'primary')->isNotEmpty();
                        $mySession      = $activeSessions->firstWhere(fn($s) => $s->schedule_id === $sched->id);
                        $enrolled       = $sched->approvedEnrollmentsCount();
                        $isPrivate      = $sched->type === 'private';
                        $checkInState   = $sched->checkInState();
                        [$opensAt]      = $sched->checkInWindow();
                    @endphp

                    <div class="px-4 py-3 {{ $checkedIn ? 'bg-[#F0FDF4]' : '' }}">
                        <div class="flex items-center gap-3">

                            {{-- Type dot --}}
                            <div class="w-2 h-2 rounded-full shrink-0 mt-0.5
                                {{ $isPrivate ? 'bg-purple-400' : 'bg-blue-400' }}"></div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <p class="text-sm font-bold {{ $checkedIn ? 'text-[#15803D]' : 'text-navy' }} truncate">
                                        {{ $sched->program?->name ?? __('messages.admin.schedules.type_private') }}
                                    </p>
                                    @if ($isPrivate)
                                        <span class="text-[9px] font-bold uppercase tracking-wide bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full shrink-0">{{ __('messages.coach.dashboard.private_badge') }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-faint">
                                    {{ $sched->location->name }}
                                    · {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                    · {{ $enrolled }} enrolled
                                </p>
                                {{-- Coaches in session --}}
                                @if ($sessionCoaches->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 mt-1">
                                        @foreach ($sessionCoaches as $cs)
                                            <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full
                                                {{ $cs->role === 'primary' ? 'bg-navy text-white' : 'bg-blue-100 text-blue-700' }}">
                                                {{ $cs->coach->user->name }}{{ $cs->role === 'primary' ? ' · ' . __('messages.coach.checkin.primary_role') : ' · ' . __('messages.coach.checkin.asst_role') }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            {{-- Action --}}
                            @if ($checkedIn)
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[10px] font-bold text-[#15803D] flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        {{ __('messages.coach.checkin.checked_in') }}
                                    </span>
                                    @if ($mySession)
                                        <button type="button"
                                                wire:click="checkOut({{ $mySession->id }})"
                                                wire:confirm="Check out from {{ $sched->program?->name ?? __('messages.admin.schedules.type_private') }}?"
                                                class="text-[10px] font-bold text-[#9B1C1C] bg-white border border-[#FECACA] px-2.5 py-1 rounded-full hover:bg-[#FEF2F2] transition-colors">
                                            {{ __('messages.coach.checkin.check_out') }}
                                        </button>
                                    @endif
                                </div>
                            @elseif ($checkInState === 'open')
                                <button type="button"
                                        @click="getGps(() => $wire.checkIn({{ $sched->id }}))"
                                        wire:loading.attr="disabled"
                                        class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-full transition-colors disabled:opacity-60
                                               {{ $hasPrimary ? 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100' : 'bg-navy text-off hover:bg-navy/90' }}">
                                    {{ $hasPrimary ? __('messages.coach.checkin.join') : __('messages.coach.checkin.btn') }}
                                </button>
                            @elseif ($checkInState === 'early')
                                <span class="shrink-0 text-[10px] font-bold text-faint bg-off border border-line px-2.5 py-1.5 rounded-full">
                                    {{ __('messages.coach.checkin.opens_at', ['time' => $opensAt->format('H:i')]) }}
                                </span>
                            @else
                                <span class="shrink-0 text-[10px] font-bold text-faint bg-off border border-line px-2.5 py-1.5 rounded-full">
                                    {{ __('messages.coach.checkin.session_ended') }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($activeSessions->isNotEmpty())
            <p class="text-[10px] font-bold uppercase tracking-widest text-faint mt-4 mb-1.5">
                {{ trans_choice('messages.coach.checkin.active_count', $activeSessions->count(), ['n' => $activeSessions->count()]) }}
            </p>
        @endif
    @endif
</div>
