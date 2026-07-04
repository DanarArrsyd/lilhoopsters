@if ($showForm)

    <x-portal.wizard-shell :step="$step" :total-steps="$totalSteps">

        {{-- Back to list --}}
        <button wire:click="cancel"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            {{ __('messages.pages.players.title') }}
        </button>

        <h1 class="text-xl sm:text-2xl font-extrabold text-navy leading-tight mb-1">
            {{ $editingId ? __('messages.players.edit_title') : __('messages.players.add_title') }}
        </h1>
        <p class="text-xs text-gray-400 mb-5">{{ __('messages.pages.players.subtitle') }}</p>

        <div class="space-y-4">
            <x-input wire:model="name" name="fld_{{ uniqid() }}" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')"
                     :label="__('messages.players.full_name')" placeholder="{{ __('messages.players.name_ph') }}"
                     required :error="$errors->first('name')" />
            <x-input type="date" wire:model="birthDate" :label="__('messages.players.dob')"
                     required :error="$errors->first('birthDate')" />
            <x-select wire:model="gender" :label="__('messages.players.gender')" :error="$errors->first('gender')">
                <option value="">{{ __('messages.players.select_gender') }}</option>
                <option value="male">{{ __('messages.players.gender_male') }}</option>
                <option value="female">{{ __('messages.players.gender_female') }}</option>
            </x-select>
            <x-input wire:model="school" name="fld_{{ uniqid() }}" autocomplete="new-password" readonly onfocus="this.removeAttribute('readonly')"
                     :label="__('messages.players.school')" placeholder="{{ __('messages.players.school_ph') }}" />
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.players.medical') }}</label>
                <textarea wire:model="medicalNotes" rows="2" aria-label="Medical notes"
                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                          placeholder="{{ __('messages.players.medical_ph') }}"></textarea>
            </div>

            @if ($editingId && $children->find($editingId)?->status === 'active')
                <div class="border-t border-line pt-4">
                    <p class="text-xs font-bold uppercase tracking-wide text-navy mb-3">{{ __('messages.players.jersey_info') }}</p>
                    <div class="grid grid-cols-2 gap-3">
                        <x-input wire:model="jerseyName" :label="__('messages.players.jersey_name')" placeholder="{{ __('messages.players.jersey_name_ph') }}" />
                        <x-input wire:model="jerseyNumber" :label="__('messages.players.number')" placeholder="{{ __('messages.players.number_ph') }}" />
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Footer nav ── --}}
        <div class="mt-6">
            <button wire:click="save" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 bg-[#15803D] text-white text-xs font-bold uppercase tracking-widest px-6 py-2.5 rounded-xl hover:bg-[#166534] active:scale-[0.97] transition-all duration-150 disabled:opacity-50">
                <svg wire:loading.remove wire:target="save" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <svg wire:loading wire:target="save" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                </svg>
                <span wire:loading.remove wire:target="save">{{ $editingId ? __('messages.players.save_changes') : __('messages.players.add_player') }}</span>
                <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
            </button>
        </div>

    </x-portal.wizard-shell>

@else

    <x-portal.content-shell>

        {{-- Header --}}
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.players.title') }}</h2>
                <p class="text-sm text-muted">{{ __('messages.pages.players.subtitle') }}</p>
            </div>
            <x-btn-add wire:click="openCreate" :label="__('messages.players.add_player')" />
        </div>

        {{-- Flash --}}
        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        {{-- Players list --}}
        @if ($children->isEmpty())
            <x-empty-state :title="__('messages.players.empty_title')" :description="__('messages.players.empty_desc')" />
        @else
            <div class="bg-surface border border-line rounded-2xl overflow-hidden divide-y divide-line">
                @foreach ($children as $child)
                    @php
                        $months = $child->ageInMonths();
                        $ageStr = $months >= 12
                            ? floor($months / 12) . 'yr' . ($months % 12 > 0 ? ' ' . ($months % 12) . 'mo' : '')
                            : $months . 'mo';
                    @endphp
                    <div class="p-4">
                        {{-- Top row: avatar + info + actions --}}
                        <div class="flex items-center gap-3">
                            {{-- Avatar --}}
                            <div class="w-10 h-10 rounded-full bg-navy/8 text-navy flex items-center justify-center font-extrabold text-base shrink-0">
                                {{ strtoupper(substr($child->name, 0, 1)) }}
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-bold text-sm text-ink">{{ $child->name }}</p>
                                    <x-badge :status="$child->status">{{ __('messages.status.'.$child->status) }}</x-badge>
                                </div>
                                <p class="text-xs text-muted mt-0.5">
                                    {{ $ageStr }} · {{ __('messages.players.gender_'.$child->gender) }}
                                    @if ($child->jersey_name || $child->jersey_number)
                                        · {{ __('messages.players.jersey_label') }}: {{ $child->jersey_name ?? '' }}@if($child->jersey_number) #{{ $child->jersey_number }}@endif
                                    @endif
                                </p>
                                @if ($child->school)
                                    <p class="text-xs text-faint">{{ $child->school }}</p>
                                @endif
                            </div>

                            {{-- Action buttons --}}
                            <div class="flex items-center gap-1.5 shrink-0">
                                @if ($child->status === 'active')
                                    <button wire:click="openQr({{ $child->id }})"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg border border-line bg-surface text-muted hover:bg-off hover:text-navy hover:border-navy/30 transition-colors"
                                            title="{{ __('messages.players.show_qr') }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                    </button>
                                @endif
                                <button wire:click="openEdit({{ $child->id }})"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg border border-line bg-surface text-muted hover:bg-off hover:text-navy hover:border-navy/30 transition-colors"
                                        title="{{ __('messages.players.edit_player') }}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        {{-- Status notices --}}
                        @if ($child->status === 'unregistered')
                            <div class="mt-3 flex items-center gap-2 px-3 py-2 bg-amber-50 rounded-xl border border-amber-100">
                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs text-amber-700">
                                    {{ __('messages.players.not_registered_pre') }}
                                    <a href="{{ route('parent.enroll') }}" class="font-semibold underline">{{ __('messages.players.enroll_now') }}</a>
                                    {{ __('messages.players.not_registered_post') }}
                                </p>
                            </div>
                        @elseif ($child->status === 'pending')
                            <div class="mt-3 flex items-center gap-2 px-3 py-2 bg-blue-50 rounded-xl border border-blue-100">
                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs text-blue-700">{{ __('messages.players.pending_notice') }}</p>
                            </div>
                        @endif

                        {{-- Enrollment count --}}
                        <p class="text-[11px] text-faint mt-2.5">{{ __('messages.players.enrollments', ['n' => $child->enrollments_count]) }}</p>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- QR Code modal --}}
        @if ($showQr && $qrChildId)
            @php $qrChild = $children->find($qrChildId); @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-navy/40" wire:click="closeQr"></div>
                <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-xs text-center">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                        <h3 class="font-extrabold uppercase tracking-tight text-navy text-sm">{{ __('messages.players.qr_code') }} — {{ $qrChild?->name }}</h3>
                        <button wire:click="closeQr" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
                    </div>
                    <div class="p-6">
                        <div class="inline-block p-3 bg-surface border-2 border-line rounded-xl">
                            {!! $qrSvg !!}
                        </div>
                        <p class="text-xs text-faint mt-3">{{ __('messages.players.qr_hint') }}</p>
                    </div>
                </div>
            </div>
        @endif

    </x-portal.content-shell>

@endif
