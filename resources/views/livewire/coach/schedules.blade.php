<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">My Schedules</h2>
        <p class="text-sm text-muted">All sessions assigned to you.</p>
    </div>

    @if ($schedules->isEmpty())
        <x-empty-state title="No schedules assigned yet" description="Your sessions will appear here once you are assigned." />
    @else
        @php $today = strtolower(now()->format('l')); @endphp
        @foreach ($days as $day)
            @php $daySessions = $schedules->where('day_of_week', $day); @endphp
            @if ($daySessions->isNotEmpty())
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-navy capitalize">{{ $day }}</h3>
                        @if ($day === $today)
                            <x-badge status="active">Today</x-badge>
                        @endif
                    </div>
                    <div class="space-y-3">
                        @foreach ($daySessions as $schedule)
                            <x-card padding="p-4">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 bg-navy/8 rounded-xl flex items-center justify-center shrink-0">
                                            <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-ink text-sm">{{ $schedule->program->name }}</p>
                                            <p class="text-xs text-faint">{{ $schedule->location->name }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-sm font-semibold text-ink">
                                            {{ \Carbon\Carbon::parse($schedule->start_time)->format('H:i') }}
                                            – {{ \Carbon\Carbon::parse($schedule->end_time)->format('H:i') }}
                                        </p>
                                        <p class="text-xs text-faint">
                                            {{ $schedule->approvedEnrollmentsCount() }} / {{ $schedule->max_capacity }} students
                                        </p>
                                    </div>
                                </div>
                            </x-card>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
</div>
