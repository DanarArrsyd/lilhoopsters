<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.leaves.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.pages.leaves.subtitle') }}</p>
    </div>

    {{-- FAB --}}
    @if ($hasProgramEnrollments)
        <button wire:click="openCreate"
                class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
        </button>
    @endif

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
                        'pending'       => ['label' => __('messages.leaves.status_pending'),       'class' => 'text-amber-700 bg-amber-50'],
                        'approved'      => ['label' => __('messages.leaves.status_approved'),      'class' => 'text-[#15803D] bg-green-50'],
                        'auto_approved' => ['label' => __('messages.leaves.status_auto'), 'class' => 'text-[#15803D] bg-green-50'],
                        'rejected'      => ['label' => __('messages.leaves.status_rejected'),      'class' => 'text-[#B91C1C] bg-red-50'],
                    ];
                    $s = $statusMap[$lr->status] ?? ['label' => ucfirst($lr->status), 'class' => 'text-muted bg-off'];
                @endphp

                <div class="px-4 py-3.5">
                    {{-- Top row: type + date + status --}}
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wide {{ $t['text'] }} {{ $t['bg'] }} px-2 py-0.5 rounded-full shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full {{ $t['dot'] }} inline-block"></span>
                                {{ $t['label'] }}
                            </span>
                            @if ($children->count() > 1 && !$filterChildId)
                                <span class="text-xs text-faint truncate">{{ $lr->child->name }}</span>
                            @endif
                        </div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded-full shrink-0 {{ $s['class'] }}">
                            {{ $s['label'] }}
                        </span>
                    </div>

                    {{-- Date --}}
                    <p class="text-sm font-semibold text-ink mt-1.5">
                        {{ $lr->leave_date->format('l, d M Y') }}
                    </p>

                    {{-- Schedule info --}}
                    <p class="text-xs text-faint mt-0.5">
                        {{ $lr->schedule->program->name ?? '—' }}
                        @if ($lr->schedule?->location)
                            · {{ $lr->schedule->location->name }}
                        @endif
                    </p>

                    {{-- Auto-approve notice --}}
                    @if ($lr->status === 'pending' && $lr->auto_approve_at)
                        <p class="text-[10px] text-muted mt-1.5 flex items-center gap-1">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ __('messages.leaves.auto_prefix') }} {{ $lr->auto_approve_at->diffForHumans() }}
                        </p>
                    @endif

                    {{-- Admin notes --}}
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

    {{-- Form modal --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="cancel"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.leaves.form_title') }}</h3>
                <button wire:click="cancel" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                {{-- 1. Player --}}
                <x-select wire:model.live="selectedChildId" :label="__('messages.leaves.player')" required
                          :error="$errors->first('selectedChildId')">
                    <option value="">{{ __('messages.leaves.select_player') }}</option>
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}">{{ $child->name }}</option>
                    @endforeach
                </x-select>

                {{-- 2. Enrollment / Package --}}
                <x-select wire:model="enrollmentId" :label="__('messages.leaves.enrollment')" required
                          :error="$errors->first('enrollmentId')"
                          :disabled="!$selectedChildId">
                    <option value="">{{ $selectedChildId ? __('messages.leaves.select_enrollment') : __('messages.leaves.select_player_first') }}</option>
                    @foreach ($enrollmentsByChild as $en)
                        <option value="{{ $en->id }}">
                            {{ $en->schedule->program->name }}
                            ({{ ucfirst($en->schedule->day_of_week) }}, {{ $en->schedule->location->name }})
                        </option>
                    @endforeach
                </x-select>

                {{-- 3. Leave Date --}}
                <div class="space-y-1">
                    <x-input type="date" wire:model="leaveDate" :label="__('messages.leaves.leave_date')"
                             min="{{ now()->subDays(7)->toDateString() }}"
                             max="{{ now()->toDateString() }}"
                             required :error="$errors->first('leaveDate')" />
                    <p class="text-[11px] text-faint">{{ __('messages.leaves.date_hint') }}</p>
                </div>

                {{-- 4. Type --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                        {{ __('messages.leaves.type') }} <span class="text-[#B91C1C]">*</span>
                    </label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                            <input type="radio" wire:model="type" value="sick" class="accent-navy"> {{ __('messages.leaves.type_sick') }}
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                            <input type="radio" wire:model="type" value="permit" class="accent-navy"> {{ __('messages.leaves.type_permit') }}
                        </label>
                    </div>
                    @error('type') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>

                {{-- 5. Reason --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.leaves.reason') }}</label>
                    <textarea wire:model="reason" rows="3"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="{{ __('messages.leaves.reason_ph') }}"></textarea>
                </div>

                <div class="p-3 bg-navy/8 rounded-xl border border-navy/20">
                    <p class="text-xs text-navy font-semibold">{{ __('messages.leaves.auto_title') }}</p>
                    <p class="text-xs text-navy/70 mt-0.5">{{ __('messages.leaves.auto_desc') }}</p>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancel">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    {{ __('messages.common.cancel') }}
                </x-btn>
                <x-btn class="flex-1" wire:click="submit" wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span wire:loading.remove wire:target="submit">{{ __('messages.common.submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('messages.common.submitting') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
