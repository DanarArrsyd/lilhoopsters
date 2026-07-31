@if ($showForm)

    <x-portal.wizard-shell :step="$step" :total-steps="$totalSteps">

        {{-- Back to list --}}
        <button wire:click="closeForm"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('messages.pages.makeup.title') }}
        </button>

        {{-- ── Stepper ── --}}
        @php
            $stepLabels = [1 => 'Which leave?', 2 => 'New schedule', 3 => 'Confirm'];
        @endphp
        <div class="flex items-start mb-5 sm:mb-8">
            @foreach ($stepLabels as $s => $label)
                <div class="flex flex-col items-center shrink-0 w-10 sm:w-14">
                    <div @class([
                        'w-6 h-6 sm:w-7 sm:h-7 rounded-full flex items-center justify-center shrink-0 text-3xs sm:text-xs font-bold',
                        'bg-[#15803D] text-white'               => $step > $s,
                        'bg-navy text-white ring-4 ring-navy/15' => $step === $s,
                        'bg-gray-100 text-gray-400'              => $step < $s,
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
                        'text-4xs sm:text-3xs mt-1 sm:mt-1.5 text-center leading-tight font-medium hidden sm:block',
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

        {{-- ════════ STEP 1: Pick approved leave ════════ --}}
        @if ($step === 1)

            <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                Which leave is this for?
            </h1>
            <p class="text-xs text-gray-400 mb-5">{{ __('messages.makeup.approved_leave') }}</p>

            @if ($approvedLeaves->isEmpty())
                <p class="text-sm text-muted py-4 text-center">No approved leaves available for a make-up class.</p>
            @else
                <div class="space-y-2">
                    @foreach ($approvedLeaves as $lr)
                        <button type="button"
                                wire:key="mk-leave-{{ $lr->id }}" wire:click="$set('leaveRequestId', {{ $lr->id }})"
                                @class([
                                    'w-full flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left',
                                    'border-navy bg-navy/5'    => $leaveRequestId == $lr->id,
                                    'border-line hover:border-navy/30 hover:bg-off' => $leaveRequestId != $lr->id,
                                ])>
                            <div class="w-10 h-10 rounded-full bg-navy flex items-center justify-center text-off font-bold text-sm shrink-0">
                                {{ strtoupper(substr($lr->child->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-ink text-sm">{{ $lr->child->name }}</p>
                                <p class="text-xs text-muted">{{ $lr->leave_date->format('d M Y') }} · {{ ucfirst($lr->type) }}</p>
                            </div>
                            @if ($leaveRequestId == $lr->id)
                                <svg class="w-5 h-5 text-navy shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
            @error('leaveRequestId')
                <p class="text-xs text-[#B91C1C] mt-2">{{ $message }}</p>
            @enderror

        {{-- ════════ STEP 2: Target schedule + date ════════ --}}
        @elseif ($step === 2)

            <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                Pick a new schedule.
            </h1>
            <p class="text-xs text-gray-400 mb-5">Choose the replacement session and date.</p>

            <div class="space-y-6">
                <div>
                    <p class="text-3xs font-bold uppercase tracking-widest text-gray-400 mb-2.5">{{ __('messages.makeup.col_schedule') }}</p>
                    <div class="space-y-2">
                        @foreach ($schedules as $s)
                            <button type="button"
                                    wire:key="mk-sched-{{ $s->id }}" wire:click="$set('targetScheduleId', {{ $s->id }})"
                                    @class([
                                        'w-full flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left',
                                        'border-navy bg-navy/5'    => $targetScheduleId == $s->id,
                                        'border-line hover:border-navy/30 hover:bg-off' => $targetScheduleId != $s->id,
                                    ])>
                                <div class="w-9 h-9 rounded-lg bg-navy/10 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-ink text-sm">{{ $s->program->name }}</p>
                                    <p class="text-xs text-muted">{{ ucfirst($s->day_of_week) }} · {{ $s->location->name }}</p>
                                </div>
                                @if ($targetScheduleId == $s->id)
                                    <svg class="w-5 h-5 text-navy shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                    @error('targetScheduleId') <p class="text-xs text-[#B91C1C] mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <p class="text-3xs font-bold uppercase tracking-widest text-gray-400 mb-2">{{ __('messages.makeup.col_date') }}</p>
                    <x-input wire:key="mk-target-date" type="date" wire:model="targetDate"
                             min="{{ now()->toDateString() }}"
                             :error="$errors->first('targetDate')" />
                </div>
            </div>

        {{-- ════════ STEP 3: Confirm ════════ --}}
        @else

            <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                Review your request.
            </h1>
            <p class="text-xs text-gray-400 mb-5">Check everything before submitting.</p>

            <div class="bg-navy rounded-xl p-5 text-off">
                <p class="text-3xs font-bold uppercase tracking-widest text-off/50 mb-4">Summary</p>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-off/10 flex items-center justify-center text-sm font-bold shrink-0">
                            {{ strtoupper(substr($selectedLeave?->child?->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-3xs text-off/50 uppercase tracking-wide">Player</p>
                            <p class="text-sm font-semibold">{{ $selectedLeave?->child?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-off/10 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-off/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-3xs text-off/50 uppercase tracking-wide">{{ __('messages.makeup.col_schedule') }}</p>
                            <p class="text-sm font-semibold">{{ $selectedSchedule?->program?->name ?? '—' }}</p>
                            <p class="text-xs text-off/60">{{ $selectedSchedule ? ucfirst($selectedSchedule->day_of_week) . ' · ' . $selectedSchedule->location->name : '' }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-off/10 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-off/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-3xs text-off/50 uppercase tracking-wide">{{ __('messages.makeup.col_date') }}</p>
                            <p class="text-sm font-semibold">{{ $targetDate ? \Carbon\Carbon::parse($targetDate)->format('l, d M Y') : '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Footer nav ── --}}
        <div class="mt-6">
            @if ($step < 3)
                <button wire:key="mk-footer-nextstep" wire:click="nextStep"
                        class="inline-flex items-center gap-2 bg-navy text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150">
                    {{ __('messages.common.next') }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            @else
                <button wire:key="mk-footer-submit" wire:click="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 bg-[#15803D] text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl hover:bg-[#166534] active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="submit" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg wire:loading wire:target="submit" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span wire:loading.remove wire:target="submit">{{ __('messages.common.submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('messages.common.submitting') }}</span>
                </button>
            @endif
        </div>

    </x-portal.wizard-shell>

@else

    <x-portal.content-shell>

        {{-- Header --}}
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.makeup.title') }}</h2>
                <p class="text-sm text-muted">{{ __('messages.pages.makeup.subtitle') }}</p>
            </div>
            @if ($approvedLeaves->isNotEmpty())
                <x-btn-add wire:click="openForm" :label="__('messages.pages.makeup.title')" />
            @endif
        </div>

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        @if ($makeUpClasses->isEmpty())
            <x-empty-state :title="__('messages.makeup.empty_title')" :description="__('messages.makeup.empty_desc')" />
        @else
            <x-card padding="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[500px]">
                        <thead>
                            <tr class="border-b border-line">
                                <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.makeup.col_child') }}</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.makeup.col_schedule') }}</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.makeup.col_date') }}</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.makeup.col_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($makeUpClasses as $mu)
                                <tr class="hover:bg-off transition-colors">
                                    <td class="py-3 px-4 font-semibold text-ink">{{ $mu->child?->name ?? '—' }}</td>
                                    <td class="py-3 px-4">
                                        @if ($mu->targetSchedule)
                                            <p class="text-ink">{{ $mu->targetSchedule->program->name }}</p>
                                            <p class="text-xs text-faint">{{ ucfirst($mu->targetSchedule->day_of_week) }} · {{ $mu->targetSchedule->location->name }}</p>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-ink">{{ $mu->target_date?->format('d M Y') ?? '—' }}</td>
                                    <td class="py-3 px-4">
                                        <x-badge :status="$mu->status">{{ __('messages.status.'.$mu->status) }}</x-badge>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-3 border-t border-line">
                    {{ $makeUpClasses->links() }}
                </div>
            </x-card>
        @endif

    </x-portal.content-shell>

@endif
