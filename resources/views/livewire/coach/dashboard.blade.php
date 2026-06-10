<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Dashboard</h2>
        <p class="text-sm text-muted">Welcome back, {{ auth()->user()->name }} — {{ now()->format('l, d F Y') }}</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <x-card padding="p-5">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-navy/8 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-navy">{{ $stats['total_schedules'] }}</p>
                    <p class="text-xs text-muted">Total Schedules</p>
                </div>
            </div>
        </x-card>

        <x-card padding="p-5">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-navy/8 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-navy">{{ $stats['today_schedules'] }}</p>
                    <p class="text-xs text-muted">Sessions Today</p>
                </div>
            </div>
        </x-card>

        <x-card padding="p-5">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-navy/8 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-extrabold text-navy">{{ $stats['active_students'] }}</p>
                    <p class="text-xs text-muted">Active Students</p>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Today's sessions --}}
    <x-card padding="p-0">
        <div class="px-4 py-3 border-b border-line">
            <h3 class="text-sm font-bold uppercase tracking-wide text-navy">Today's Sessions</h3>
        </div>

        @if ($todaySchedules->isEmpty())
            <x-empty-state title="No sessions scheduled for today" description="Enjoy your day off." />
        @else
            <div class="divide-y divide-line">
                @foreach ($todaySchedules as $schedule)
                    <div class="px-4 py-4 flex items-center justify-between gap-4">
                        <div>
                            <p class="font-semibold text-ink text-sm">{{ $schedule->program->name }}</p>
                            <p class="text-xs text-faint">{{ $schedule->location->name }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-sm font-semibold text-ink">
                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                – {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                            </p>
                            <p class="text-xs text-faint">
                                {{ $schedule->enrollments->where('status', 'approved')->count() }} / {{ $schedule->max_capacity }} students
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>
</div>
