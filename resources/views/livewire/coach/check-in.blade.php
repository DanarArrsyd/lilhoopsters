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
}">
    {{-- Header --}}
    <div class="mb-5">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Check In</h2>
        <p class="text-sm text-muted">
            {{ now()->format('l, d F Y') }} · You can check in to multiple sessions today.
        </p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif
    @if (session('error'))
        <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
    @endif

    {{-- All today's schedules (private + regular) --}}
    @php
        $allSchedules = $privateSchedules->concat($regularSchedules)->sortBy('start_time')->values();
    @endphp

    @if ($allSchedules->isEmpty())
        {{-- No sessions today --}}
        <div class="bg-surface border border-line rounded-2xl p-6 text-center mb-4">
            <p class="text-sm font-semibold text-ink mb-0.5">No sessions today</p>
            <p class="text-xs text-faint">There are no active schedules for {{ now()->format('l') }}.</p>
        </div>

        {{-- Upcoming --}}
        @if ($upcomingDate && $upcomingSchedules->isNotEmpty())
            <p class="text-[10px] font-bold uppercase tracking-widest text-faint mb-2.5">Next Session</p>
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
                                <p class="text-sm font-semibold text-ink truncate">{{ $sched->program->name }}</p>
                                <p class="text-xs text-faint">{{ $sched->location->name }} · {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}</p>
                            </div>
                            <span class="text-[9px] font-bold uppercase tracking-wide px-1.5 py-0.5 rounded-full shrink-0
                                {{ $sched->type === 'private' ? 'bg-purple-100 text-purple-700' : 'bg-blue-50 text-blue-600' }}">
                                {{ $sched->type }}
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
                                        {{ $sched->program->name }}
                                    </p>
                                    @if ($isPrivate)
                                        <span class="text-[9px] font-bold uppercase tracking-wide bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded-full shrink-0">Private</span>
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
                                                {{ $cs->coach->user->name }}{{ $cs->role === 'primary' ? ' · Primary' : ' · Asst.' }}
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
                                        In
                                    </span>
                                    @if ($mySession)
                                        <button type="button"
                                                wire:click="checkOut({{ $mySession->id }})"
                                                wire:confirm="Check out from {{ $sched->program->name }}?"
                                                class="text-[10px] font-bold text-[#9B1C1C] bg-white border border-[#FECACA] px-2.5 py-1 rounded-full hover:bg-[#FEF2F2] transition-colors">
                                            Out
                                        </button>
                                    @endif
                                </div>
                            @else
                                <button type="button"
                                        @click="getGps(() => $wire.checkIn({{ $sched->id }}))"
                                        wire:loading.attr="disabled"
                                        class="shrink-0 text-xs font-bold px-3 py-1.5 rounded-full transition-colors disabled:opacity-60
                                               {{ $hasPrimary ? 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100' : 'bg-navy text-off hover:bg-navy/90' }}">
                                    {{ $hasPrimary ? 'Join' : 'Check In' }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($activeSessions->isNotEmpty())
            <p class="text-[10px] font-bold uppercase tracking-widest text-faint mt-4 mb-1.5">
                {{ $activeSessions->count() }} session{{ $activeSessions->count() > 1 ? 's' : '' }} active today
            </p>
        @endif
    @endif
</div>
