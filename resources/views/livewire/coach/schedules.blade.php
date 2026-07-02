<div class="max-w-6xl mx-auto">
    <x-admin.page-header :title="__('messages.coach.schedules.title')" :subtitle="__('messages.coach.schedules.subtitle')" />

    @if ($schedules->isEmpty())
        <x-empty-state :title="__('messages.coach.schedules.empty_title')" :description="__('messages.coach.schedules.empty_desc')" />
    @else
        @php $today = strtolower(now()->format('l')); @endphp
        @foreach ($days as $day)
            @php $daySessions = $schedules->where('day_of_week', $day); @endphp
            @if ($daySessions->isNotEmpty())
                <div class="mb-6">
                    <div class="flex items-center gap-2 mb-3">
                        <h3 class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.coach.days.'.$day) }}</h3>
                        @if ($day === $today)
                            <x-badge status="active">{{ __('messages.coach.schedules.today_badge') }}</x-badge>
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
                                            {{ __('messages.coach.schedules.students', ['n' => $schedule->approvedEnrollmentsCount(), 'max' => $schedule->max_capacity]) }}
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
