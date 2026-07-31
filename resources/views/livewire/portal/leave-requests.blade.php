@if ($showForm)

    <x-portal.wizard-shell :step="$step" :total-steps="$totalSteps">

        {{-- Back to list --}}
        <button wire:click="cancel"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('messages.pages.leaves.title') }}
        </button>

        {{-- ── Stepper ── --}}
        @php
            $stepLabels = [1 => 'Who?', 2 => 'When & Why?', 3 => 'Confirm'];
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

        {{-- ════════ STEP 1: Pick child ════════ --}}
        @if ($step === 1)

            <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                {{ __('messages.pages.leaves.title') }}
            </h1>
            <p class="text-xs text-gray-400 mb-5">Select the player for this leave request.</p>

            <div class="space-y-2">
                @foreach ($children as $child)
                    <button type="button"
                            wire:key="lv-child-{{ $child->id }}" wire:click="$set('selectedChildId', {{ $child->id }})"
                            @class([
                                'w-full flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left',
                                'border-navy bg-navy/5'    => $selectedChildId == $child->id,
                                'border-line hover:border-navy/30 hover:bg-off' => $selectedChildId != $child->id,
                            ])>
                        <div class="w-10 h-10 rounded-full bg-navy flex items-center justify-center text-off font-bold text-sm shrink-0">
                            {{ strtoupper(substr($child->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-ink text-sm">{{ $child->name }}</p>
                            <p class="text-xs text-muted">{{ $child->birth_date?->format('d M Y') ?? '' }}</p>
                        </div>
                        @if ($selectedChildId == $child->id)
                            <svg class="w-5 h-5 text-navy shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
            @error('selectedChildId')
                <p class="text-xs text-[#B91C1C] mt-2">{{ $message }}</p>
            @enderror

        {{-- ════════ STEP 2: Schedule + date + type ════════ --}}
        @elseif ($step === 2)

            <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                When and why?
            </h1>
            <p class="text-xs text-gray-400 mb-5">Pick the class and date you'll be missing.</p>

            <div class="space-y-6">
                {{-- Schedule cards --}}
                <div>
                    <p class="text-3xs font-bold uppercase tracking-widest text-gray-400 mb-2.5">Class / Schedule</p>
                    @if ($enrollmentsByChild->isEmpty())
                        <p class="text-sm text-muted py-4 text-center">No active enrollments for this player.</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($enrollmentsByChild as $en)
                                <button type="button"
                                        wire:key="lv-enroll-{{ $en->id }}" wire:click="$set('enrollmentId', {{ $en->id }})"
                                        @class([
                                            'w-full flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all text-left',
                                            'border-navy bg-navy/5'    => $enrollmentId == $en->id,
                                            'border-line hover:border-navy/30 hover:bg-off' => $enrollmentId != $en->id,
                                        ])>
                                    <div class="w-9 h-9 rounded-lg bg-navy/10 flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-ink text-sm">{{ $en->schedule->program->name }}</p>
                                        <p class="text-xs text-muted">{{ ucfirst($en->schedule->day_of_week) }} · {{ $en->schedule->location->name }}</p>
                                    </div>
                                    @if ($enrollmentId == $en->id)
                                        <svg class="w-5 h-5 text-navy shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @error('enrollmentId') <p class="text-xs text-[#B91C1C] mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Date --}}
                <div>
                    <p class="text-3xs font-bold uppercase tracking-widest text-gray-400 mb-2">Leave Date</p>
                    <x-input wire:key="lv-date" type="date" wire:model="leaveDate"
                             min="{{ now()->subDays(7)->toDateString() }}"
                             max="{{ now()->toDateString() }}"
                             :error="$errors->first('leaveDate')" />
                    <p class="text-2xs text-faint mt-1">{{ __('messages.leaves.date_hint') }}</p>
                </div>

                {{-- Type toggle --}}
                <div>
                    <p class="text-3xs font-bold uppercase tracking-widest text-gray-400 mb-3">Leave Type <span class="text-[#B91C1C]">*</span></p>
                    <div class="grid grid-cols-2 gap-3">
                        <button type="button" wire:key="lv-type-sick" wire:click="$set('type','sick')"
                                @class([
                                    'flex flex-col items-center gap-2 py-4 px-4 rounded-xl border-2 transition-all',
                                    'border-amber-500 bg-amber-50'       => $type === 'sick',
                                    'border-line hover:border-amber-300' => $type !== 'sick',
                                ])>
                            <svg class="w-7 h-7 {{ $type === 'sick' ? 'text-amber-600' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                            </svg>
                            <span class="text-sm font-semibold {{ $type === 'sick' ? 'text-amber-700' : 'text-muted' }}">{{ __('messages.leaves.type_sick') }}</span>
                        </button>
                        <button type="button" wire:key="lv-type-permit" wire:click="$set('type','permit')"
                                @class([
                                    'flex flex-col items-center gap-2 py-4 px-4 rounded-xl border-2 transition-all',
                                    'border-navy bg-navy/5'            => $type === 'permit',
                                    'border-line hover:border-navy/30' => $type !== 'permit',
                                ])>
                            <svg class="w-7 h-7 {{ $type === 'permit' ? 'text-navy' : 'text-muted' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="text-sm font-semibold {{ $type === 'permit' ? 'text-navy' : 'text-muted' }}">{{ __('messages.leaves.type_permit') }}</span>
                        </button>
                    </div>
                    @error('type') <p class="text-xs text-[#B91C1C] mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

        {{-- ════════ STEP 3: Confirm ════════ --}}
        @else

            <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
                Review your request.
            </h1>
            <p class="text-xs text-gray-400 mb-5">Check everything before submitting.</p>

            <div class="space-y-5">
                {{-- Summary card --}}
                <div class="bg-navy rounded-xl p-5 text-off">
                    <p class="text-3xs font-bold uppercase tracking-widest text-off/50 mb-4">Summary</p>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-off/10 flex items-center justify-center text-sm font-bold shrink-0">
                                {{ strtoupper(substr($children->firstWhere('id', $selectedChildId)?->name ?? '?', 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-3xs text-off/50 uppercase tracking-wide">Player</p>
                                <p class="text-sm font-semibold">{{ $children->firstWhere('id', $selectedChildId)?->name ?? '—' }}</p>
                            </div>
                        </div>
                        @if ($selectedEnrollment)
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-off/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-off/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-3xs text-off/50 uppercase tracking-wide">Class</p>
                                <p class="text-sm font-semibold">{{ $selectedEnrollment->schedule->program->name }}</p>
                                <p class="text-xs text-off/60">{{ ucfirst($selectedEnrollment->schedule->day_of_week) }} · {{ $selectedEnrollment->schedule->location->name }}</p>
                            </div>
                        </div>
                        @endif
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-off/10 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-off/70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-3xs text-off/50 uppercase tracking-wide">Date</p>
                                <p class="text-sm font-semibold">{{ $leaveDate ? \Carbon\Carbon::parse($leaveDate)->format('l, d M Y') : '—' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full {{ $type === 'sick' ? 'bg-amber-400/20' : 'bg-off/10' }} flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 {{ $type === 'sick' ? 'text-amber-300' : 'text-off/70' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-3xs text-off/50 uppercase tracking-wide">Type</p>
                                <p class="text-sm font-semibold capitalize">{{ $type ?: '—' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reason (optional) --}}
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wide text-navy mb-2">
                        {{ __('messages.leaves.reason') }}
                        <span class="text-faint font-normal normal-case tracking-normal">(optional)</span>
                    </label>
                    <textarea wire:key="lv-reason" wire:model="reason" rows="3"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="{{ __('messages.leaves.reason_ph') }}"></textarea>
                </div>

                {{-- Auto-approve notice --}}
                <div class="flex items-start gap-2.5 p-4 bg-navy/5 rounded-xl border border-navy/15">
                    <svg class="w-4 h-4 text-navy shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-xs font-semibold text-navy">{{ __('messages.leaves.auto_title') }}</p>
                        <p class="text-xs text-muted mt-0.5">{{ __('messages.leaves.auto_desc') }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Footer nav ── --}}
        <div class="mt-6">
            @if ($step < 3)
                <button wire:key="lv-footer-nextstep" wire:click="nextStep"
                        class="inline-flex items-center gap-2 bg-navy text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all duration-150">
                    {{ __('messages.common.next') }}
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            @else
                <button wire:key="lv-footer-submit" wire:click="submit" wire:loading.attr="disabled"
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
                <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.leaves.title') }}</h2>
                <p class="text-sm text-muted">{{ __('messages.pages.leaves.subtitle') }}</p>
            </div>
            @if ($hasProgramEnrollments)
                <x-btn-add wire:click="openCreate" :label="__('messages.leaves.form_title')" />
            @endif
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        @if (!$hasProgramEnrollments)
            <x-empty-state :title="__('messages.leaves.no_enroll_title')" :description="__('messages.leaves.no_enroll_desc')" />
        @else

            {{-- Child filter tabs --}}
            @if ($children->count() > 1)
                <div class="flex gap-2 flex-wrap mb-4 overflow-x-auto pb-1" style="scrollbar-width:none;">
                    @foreach ($children as $child)
                        <button wire:click="selectFilterChild({{ $child->id }})"
                                class="flex-shrink-0 text-xs font-bold px-3 py-1.5 rounded-full border transition-all whitespace-nowrap
                                    {{ $filterChildId == $child->id
                                        ? 'bg-navy text-off border-transparent'
                                        : 'bg-surface text-muted border-line hover:border-navy/30 hover:text-navy' }}">
                            {{ $child->name }}
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Leave request list --}}
            <div class="bg-surface border border-line rounded-2xl overflow-hidden divide-y divide-line">
                @forelse ($leaveRequests as $lr)
                    @php
                        $typeMap = [
                            'sick'   => ['label' => __('messages.leaves.type_sick'),   'dot' => 'bg-amber-400',  'text' => 'text-amber-700', 'bg' => 'bg-amber-50'],
                            'permit' => ['label' => __('messages.leaves.type_permit'), 'dot' => 'bg-blue-400',   'text' => 'text-blue-700',  'bg' => 'bg-blue-50'],
                        ];
                        $t = $typeMap[$lr->type] ?? ['label' => ucfirst($lr->type), 'dot' => 'bg-gray-300', 'text' => 'text-muted', 'bg' => 'bg-off'];

                        $statusMap = [
                            'pending'       => ['label' => __('messages.leaves.status_pending'),  'class' => 'text-amber-700 bg-amber-50'],
                            'approved'      => ['label' => __('messages.leaves.status_approved'), 'class' => 'text-[#15803D] bg-green-50'],
                            'auto_approved' => ['label' => __('messages.leaves.status_auto'),     'class' => 'text-[#15803D] bg-green-50'],
                            'rejected'      => ['label' => __('messages.leaves.status_rejected'), 'class' => 'text-[#B91C1C] bg-red-50'],
                        ];
                        $s = $statusMap[$lr->status] ?? ['label' => ucfirst($lr->status), 'class' => 'text-muted bg-off'];
                    @endphp

                    <div class="px-4 py-3.5">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-flex items-center gap-1.5 text-2xs font-bold uppercase tracking-wide {{ $t['text'] }} {{ $t['bg'] }} px-2 py-0.5 rounded-full shrink-0">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $t['dot'] }} inline-block"></span>
                                    {{ $t['label'] }}
                                </span>
                                @if ($children->count() > 1 && !$filterChildId)
                                    <span class="text-xs text-faint truncate">{{ $lr->child->name }}</span>
                                @endif
                            </div>
                            <span class="text-2xs font-bold px-2 py-0.5 rounded-full shrink-0 {{ $s['class'] }}">
                                {{ $s['label'] }}
                            </span>
                        </div>

                        <p class="text-sm font-semibold text-ink mt-1.5">
                            {{ $lr->leave_date->format('l, d M Y') }}
                        </p>

                        <p class="text-xs text-faint mt-0.5">
                            {{ $lr->schedule->program->name ?? '—' }}
                            @if ($lr->schedule?->location)
                                · {{ $lr->schedule->location->name }}
                            @endif
                        </p>

                        @if ($lr->status === 'pending' && $lr->auto_approve_at)
                            <p class="text-3xs text-muted mt-1.5 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ __('messages.leaves.auto_prefix') }} {{ $lr->auto_approve_at->diffForHumans() }}
                            </p>
                        @endif

                        @if ($lr->admin_notes)
                            <p class="text-xs text-[#B91C1C] mt-1.5 italic">{{ $lr->admin_notes }}</p>
                        @endif
                    </div>
                @empty
                    <div class="py-2">
                        <x-empty-state :title="__('messages.leaves.empty_title')" :description="__('messages.leaves.empty_desc')" />
                    </div>
                @endforelse
            </div>

        @endif

    </x-portal.content-shell>

@endif
