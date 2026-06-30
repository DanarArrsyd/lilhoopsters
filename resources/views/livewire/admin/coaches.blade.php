<div>

    @if ($showModal)

        {{-- ═══ INLINE WIZARD ═══ --}}
        <div class="max-w-2xl mx-auto">

            {{-- Back link --}}
            <button wire:click="$set('showModal', false)"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.admin.coaches.title') }}
            </button>

            {{-- Wizard card --}}
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">

                {{-- Header + progress --}}
                <div class="px-6 pt-6 pb-5 border-b border-line">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy">
                                {{ $editingId ? __('messages.admin.coaches.modal_edit') : __('messages.admin.coaches.modal_new') }}
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                {{ __('messages.admin.coaches.step_account') }} &amp; {{ __('messages.admin.coaches.step_profile') }}
                            </p>
                        </div>
                        <span class="text-sm font-bold text-faint">{{ $step }}/2</span>
                    </div>

                    {{-- Progress bar --}}
                    <div class="h-1.5 bg-line rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-navy rounded-full transition-all duration-300"
                             style="width: {{ $step / 2 * 100 }}%"></div>
                    </div>

                    {{-- Step dots --}}
                    <div class="flex items-center">
                        @foreach ([1 => __('messages.admin.coaches.step_account'), 2 => __('messages.admin.coaches.step_profile')] as $n => $label)
                            <div class="flex items-center gap-2">
                                <div @class([
                                    'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-colors',
                                    'bg-[#15803D] text-white' => $step > $n,
                                    'bg-navy text-off'        => $step === $n,
                                    'bg-line text-faint'      => $step < $n,
                                ])>
                                    @if ($step > $n)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $n }}
                                    @endif
                                </div>
                                <span @class(['text-sm font-semibold transition-colors', 'text-navy' => $step === $n, 'text-faint' => $step !== $n])>
                                    {{ $label }}
                                </span>
                            </div>
                            @if ($n < 2)
                                <div class="flex-1 h-px bg-line mx-3"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Form content --}}
                <div class="px-6 py-6">

                    {{-- ── Step 1: Account ── --}}
                    @if ($step === 1)
                        <div class="space-y-5">
                            <x-input wire:model="coach_name"
                                     label="{{ __('messages.admin.coaches.label_name') }}"
                                     placeholder="Coach name"
                                     required :error="$errors->first('coach_name')" />
                            <x-input wire:model="coach_email" type="email"
                                     label="{{ __('messages.admin.coaches.label_email') }}"
                                     placeholder="coach@example.com"
                                     required :error="$errors->first('coach_email')" />
                            <x-input wire:model="coach_password" type="password"
                                     label="{{ $editingId ? __('messages.admin.coaches.label_new_password') : __('messages.admin.coaches.label_password') }}"
                                     placeholder="{{ $editingId ? __('messages.admin.coaches.label_keep_pw') : __('messages.admin.coaches.label_password_ph') }}"
                                     :required="!$editingId" :error="$errors->first('coach_password')" />
                        </div>
                    @endif

                    {{-- ── Step 2: Profile ── --}}
                    @if ($step === 2)
                        <div class="space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <x-input wire:model="phone"
                                         label="{{ __('messages.admin.coaches.label_phone') }}"
                                         placeholder="081234567890"
                                         required :error="$errors->first('phone')" />
                                <x-input wire:model="specialization"
                                         label="{{ __('messages.admin.coaches.label_spec') }}"
                                         placeholder="e.g. Dribbling, Defense"
                                         :error="$errors->first('specialization')" />
                            </div>

                            {{-- Location chips --}}
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy mb-3">
                                    {{ __('messages.admin.coaches.label_locations') }}
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($locations as $loc)
                                        <button type="button"
                                                wire:click="toggleLocation({{ $loc->id }})"
                                                @class([
                                                    'px-4 py-2 rounded-full border-2 text-sm font-semibold transition-all duration-150',
                                                    'border-navy bg-navy text-off shadow-sm' => in_array($loc->id, $selectedLocations),
                                                    'border-line bg-surface text-muted hover:border-navy/30 hover:text-navy' => !in_array($loc->id, $selectedLocations),
                                                ])>
                                            {{ $loc->name }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Active toggle --}}
                            <div class="flex items-center justify-between rounded-xl bg-off px-5 py-4 border border-line">
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ __('messages.admin.coaches.label_active') }}</p>
                                    <p class="text-xs text-muted mt-0.5">Coach can log in and take sessions</p>
                                </div>
                                <button type="button" wire:click="$set('is_active', {{ $is_active ? 'false' : 'true' }})"
                                        @class([
                                            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200',
                                            'bg-navy' => $is_active,
                                            'bg-line' => !$is_active,
                                        ])>
                                    <span @class([
                                        'inline-block h-4 w-4 rounded-full bg-white shadow-sm transition-transform duration-200',
                                        'translate-x-6' => $is_active,
                                        'translate-x-1' => !$is_active,
                                    ])></span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-line flex gap-3">
                    @if ($step === 1)
                        <button wire:click="$set('showModal', false)"
                                class="px-5 py-2.5 text-sm font-semibold text-muted bg-off border border-line rounded-xl hover:bg-line/40 transition-colors">
                            {{ __('messages.common.cancel') }}
                        </button>
                        <button wire:click="nextStep"
                                class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-off bg-navy rounded-xl hover:bg-navy/90 transition-colors">
                            {{ __('messages.common.next') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <button wire:click="prevStep"
                                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-muted bg-off border border-line rounded-xl hover:bg-line/40 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            {{ __('messages.common.back') }}
                        </button>
                        <button wire:click="save" wire:loading.attr="disabled"
                                class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-off bg-navy rounded-xl hover:bg-navy/90 disabled:opacity-50 transition-colors">
                            <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            <span wire:loading.remove wire:target="save">{{ $editingId ? __('messages.common.update') : __('messages.common.save') }}</span>
                            <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                        </button>
                    @endif
                </div>

            </div>
        </div>

    @else

        {{-- ═══ LIST VIEW ═══ --}}

        {{-- Header --}}
        <div class="mb-6">
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.coaches.title') }}</h2>
            <p class="text-sm text-muted">{{ __('messages.admin.coaches.subtitle') }}</p>
        </div>

        <button wire:click="openCreate"
                class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
        </button>

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        <x-card class="mb-4" padding="p-4">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." />
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_coach') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_phone') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_specialization') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_locations') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_status') }}</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($coaches as $coach)
                            <tr class="hover:bg-off transition-colors">
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 bg-navy/8 text-navy">
                                            {{ strtoupper(substr($coach->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-ink">{{ $coach->user->name }}</p>
                                            <p class="text-xs text-faint">{{ $coach->user->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-muted">{{ $coach->phone }}</td>
                                <td class="py-3 px-4 text-muted">{{ $coach->specialization ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    @forelse ($coach->locations as $loc)
                                        <span class="inline-flex items-center bg-navy/8 text-navy text-[11px] font-semibold px-2 py-0.5 rounded-full mr-1">
                                            {{ $loc->name }}
                                        </span>
                                    @empty
                                        <span class="text-faint text-xs">—</span>
                                    @endforelse
                                </td>
                                <td class="py-3 px-4">
                                    <x-badge :status="$coach->is_active ? 'active' : 'inactive'">
                                        {{ $coach->is_active ? __('messages.status.active') : __('messages.status.inactive') }}
                                    </x-badge>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2 justify-end">
                                        <x-btn variant="edit" size="sm"
                                            wire:click="openEdit({{ $coach->id }})"
                                            wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            {{ __('messages.common.edit') }}
                                        </x-btn>
                                        <x-btn variant="{{ $coach->is_active ? 'warning' : 'success' }}" size="sm"
                                            wire:click="toggleActive({{ $coach->id }})"
                                            wire:loading.attr="disabled">
                                            @if ($coach->is_active)
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                {{ __('messages.common.deactivate') }}
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                                {{ __('messages.common.activate') }}
                                            @endif
                                        </x-btn>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-2">
                                    <x-empty-state :title="__('messages.admin.coaches.empty_title')" :description="__('messages.admin.coaches.empty_desc')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($coaches->hasPages())
                <div class="px-4 py-3 border-t border-line">
                    {{ $coaches->links() }}
                </div>
            @endif
        </x-card>

    @endif
</div>
