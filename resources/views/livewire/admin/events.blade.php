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
                {{ __('messages.admin.events.title') }}
            </button>

            {{-- Wizard card --}}
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">

                {{-- Header + progress --}}
                <div class="px-6 pt-6 pb-5 border-b border-line">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy">
                                {{ $editingId ? __('messages.admin.events.modal_edit') : __('messages.admin.events.modal_new') }}
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                {{ __('messages.admin.events.step_info') }} &amp; {{ __('messages.admin.events.step_details') }}
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
                        @foreach ([1 => __('messages.admin.events.step_info'), 2 => __('messages.admin.events.step_details')] as $n => $label)
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

                    {{-- ── Step 1: Name + Dates + Location + Program ── --}}
                    @if ($step === 1)
                        <div class="space-y-5">
                            <x-input wire:model="name"
                                     label="{{ __('messages.admin.events.label_name') }}"
                                     placeholder="e.g. Summer Championship"
                                     required :error="$errors->first('name')" />

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-input wire:model="start_date" type="date"
                                         label="{{ __('messages.admin.events.label_start_date') }}"
                                         required :error="$errors->first('start_date')" />
                                <x-input wire:model="end_date" type="date"
                                         label="{{ __('messages.admin.events.label_end_date') }}"
                                         required :error="$errors->first('end_date')" />
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.events.label_location') }}</label>
                                    <x-select wire:model="location_id">
                                        <option value="">{{ __('messages.admin.events.label_all_locs') }}</option>
                                        @foreach ($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.events.label_program') }}</label>
                                    <x-select wire:model="program_id">
                                        <option value="">{{ __('messages.admin.events.label_all_progs') }}</option>
                                        @foreach ($programs as $prog)
                                            <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-line">
                            <button wire:click="$set('showModal', false)"
                                    class="text-sm font-semibold text-muted hover:text-navy transition-colors px-4 py-2">
                                {{ __('messages.common.cancel') }}
                            </button>
                            <button wire:click="nextStep" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 bg-navy text-off text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="nextStep">{{ __('messages.common.next') }}</span>
                                <span wire:loading wire:target="nextStep">...</span>
                                <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>

                    {{-- ── Step 2: Description + Active + Registration ── --}}
                    @elseif ($step === 2)
                        <div class="space-y-5">

                            {{-- Description --}}
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.events.label_description') }}</label>
                                <textarea wire:model="description" rows="3"
                                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                          placeholder="{{ __('messages.admin.events.desc_ph') }}"></textarea>
                                @error('description') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                            </div>

                            {{-- Active toggle --}}
                            <div class="flex items-center justify-between px-5 py-4 bg-off rounded-xl border border-line">
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ __('messages.admin.events.label_active') }}</p>
                                    <p class="text-xs text-muted mt-0.5">{{ __('messages.admin.events.freeze_desc') }}</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                                    <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                    <div class="w-10 h-6 bg-line peer-focus:outline-none rounded-full peer
                                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:bg-navy"></div>
                                </label>
                            </div>

                            {{-- Open for Registration toggle --}}
                            <div class="border-t border-line pt-5 space-y-4">
                                <div class="flex items-center justify-between px-5 py-4 bg-off rounded-xl border border-line">
                                    <p class="text-sm font-semibold text-ink">{{ __('messages.admin.events.label_open_reg') }}</p>
                                    <label class="relative inline-flex items-center cursor-pointer shrink-0 ml-4">
                                        <input type="checkbox" wire:model.live="is_registerable" class="sr-only peer">
                                        <div class="w-10 h-6 bg-line peer-focus:outline-none rounded-full peer
                                                    peer-checked:after:translate-x-full peer-checked:after:border-white
                                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                    after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                                    peer-checked:bg-navy"></div>
                                    </label>
                                </div>

                                @if ($is_registerable)
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <x-input wire:model="price" type="number"
                                                 label="{{ __('messages.admin.events.label_price') }}"
                                                 placeholder="0 = free"
                                                 :error="$errors->first('price')" />
                                        <x-input wire:model="capacity" type="number"
                                                 label="{{ __('messages.admin.events.label_capacity') }}"
                                                 placeholder="Blank = unlimited"
                                                 :error="$errors->first('capacity')" />
                                    </div>
                                    <p class="text-[11px] text-faint">{{ __('messages.admin.events.free_hint') }}</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 mt-6 pt-5 border-t border-line">
                            <button wire:click="back"
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-navy transition-colors px-4 py-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                {{ __('messages.common.back') }}
                            </button>
                            <button wire:click="save" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 bg-navy text-off text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all disabled:opacity-50">
                                <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span wire:loading.remove wire:target="save">
                                    {{ $editingId ? __('messages.common.update') : __('messages.common.save') }}
                                </span>
                                <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    @else

        {{-- ═══ LIST VIEW ═══ --}}
        <div class="max-w-6xl mx-auto">

        <x-admin.page-header :title="__('messages.admin.events.title')" :subtitle="__('messages.admin.events.subtitle')">
            <x-slot name="action">
                <x-btn variant="add" wire:click="openCreate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('messages.admin.events.new') }}
                </x-btn>
            </x-slot>
        </x-admin.page-header>

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert type="danger" class="mb-4">{{ session('error') }}</x-alert>
        @endif

        <x-card class="mb-4" padding="p-4">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Search events..." />
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[760px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.events.col_event') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.events.col_period') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.events.col_scope') }}</th>
                            <th class="text-right py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.events.col_frozen') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.events.col_status') }}</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($events as $event)
                            @php
                                $days = $event->start_date->diffInDays($event->end_date) + 1;
                                if (! $event->is_active)                       $state = [__('messages.admin.events.state_inactive'), 'bg-line text-faint'];
                                elseif (today()->lt($event->start_date))       $state = [__('messages.admin.events.state_upcoming'), 'bg-[#1D4ED8]/10 text-[#1D4ED8]'];
                                elseif (today()->gt($event->end_date))         $state = [__('messages.admin.events.state_past'), 'bg-navy/10 text-navy'];
                                else                                           $state = [__('messages.admin.events.state_running'), 'bg-[#15803D]/10 text-[#15803D]'];
                            @endphp
                            <tr class="hover:bg-off transition-colors" wire:key="event-{{ $event->id }}">
                                <td class="py-3 px-4">
                                    <p class="font-semibold text-ink">{{ $event->name }}</p>
                                    @if ($event->description)
                                        <p class="text-xs text-muted max-w-xs truncate">{{ $event->description }}</p>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-muted">
                                    {{ $event->start_date->format('d M Y') }} – {{ $event->end_date->format('d M Y') }}
                                    <span class="block text-xs text-faint">{{ $days }} days</span>
                                </td>
                                <td class="py-3 px-4 text-muted">
                                    {{ $event->location?->name ?? __('messages.admin.events.all_locations') }}
                                    <span class="block text-xs text-faint">{{ $event->program?->name ?? __('messages.admin.events.all_programs') }}</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="font-bold text-navy">{{ $event->enrollments_count }}</span>
                                    <span class="block text-xs text-faint">packages</span>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex text-[11px] font-bold px-2 py-0.5 rounded-md {{ $state[1] }}">{{ $state[0] }}</span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2 justify-end">
                                        @if ($event->is_registerable)
                                            <x-btn variant="add" size="sm" wire:click="openParticipants({{ $event->id }})" wire:loading.attr="disabled">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                {{ $event->registrations_count }}@if($event->capacity)/{{ $event->capacity }}@endif
                                            </x-btn>
                                        @endif
                                        <x-btn variant="edit" size="sm" wire:click="openEdit({{ $event->id }})" wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            {{ __('messages.common.edit') }}
                                        </x-btn>
                                        <x-btn variant="{{ $event->is_active ? 'warning' : 'success' }}" size="sm"
                                               wire:click="toggleActive({{ $event->id }})" wire:loading.attr="disabled">
                                            {{ $event->is_active ? __('messages.common.deactivate') : __('messages.common.activate') }}
                                        </x-btn>
                                        <x-btn variant="danger" size="sm"
                                               wire:click="confirmDelete({{ $event->id }})"
                                               wire:confirm="Delete this event? Frozen packages will be reverted."
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            {{ __('messages.common.delete') }}
                                        </x-btn>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-2">
                                    <x-empty-state :title="__('messages.admin.events.empty_title')" :description="__('messages.admin.events.empty_desc')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($events->hasPages())
                <div class="px-4 py-3 border-t border-line">{{ $events->links() }}</div>
            @endif
        </x-card>

    @endif

    {{-- Participants panel (floating modal — always visible regardless of wizard state) --}}
    @if ($managingEvent)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="closeParticipants"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-surface">
                    <div>
                        <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $managingEvent->name }} — {{ __('messages.admin.events.participants_title') }}</h3>
                        <p class="text-xs text-muted">
                            {{ $managingEvent->takenCount() }}@if($managingEvent->capacity)/{{ $managingEvent->capacity }}@endif registered
                            · {{ $managingEvent->isPaid() ? 'Rp ' . number_format($managingEvent->price, 0, ',', '.') : __('messages.admin.events.free_badge') }}
                        </p>
                    </div>
                    <button wire:click="closeParticipants" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>

                <div class="p-6 space-y-4">
                    @if ($managingEvent->isFull())
                        <p class="text-sm text-[#B45309] bg-[#B45309]/10 border border-[#B45309]/20 rounded-lg px-3 py-2">{{ __('messages.admin.events.event_full') }}</p>
                    @else
                        <div class="flex gap-2 items-end">
                            <div class="flex-1 space-y-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.events.add_child') }}</label>
                                <x-select wire:model="addChildId">
                                    <option value="">{{ __('messages.admin.events.select_child') }}</option>
                                    @foreach ($availableChildren as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }} — {{ $c->parent?->name }}</option>
                                    @endforeach
                                </x-select>
                            </div>
                            <x-btn variant="add" wire:click="addParticipant" wire:loading.attr="disabled" wire:target="addParticipant">{{ __('messages.common.add') }}</x-btn>
                        </div>
                    @endif

                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.events.attendance_for') }}</label>
                        <input type="date" wire:model.live="attendanceDate"
                               min="{{ $managingEvent->start_date->toDateString() }}"
                               max="{{ $managingEvent->end_date->toDateString() }}"
                               class="text-xs border border-line rounded-lg px-2.5 py-1.5 text-ink bg-off focus:outline-none focus:ring-2 focus:ring-navy/20" />
                    </div>

                    <div class="border border-line rounded-xl overflow-hidden">
                        @if ($managingEvent->registrations->isEmpty())
                            <p class="px-4 py-6 text-center text-sm text-muted">{{ __('messages.admin.events.no_participants') }}</p>
                        @else
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-[11px] uppercase tracking-wide text-faint border-b border-line">
                                        <th class="text-left font-semibold px-4 py-2.5">{{ __('messages.admin.events.col_child') }}</th>
                                        <th class="text-left font-semibold px-3 py-2.5">{{ __('messages.admin.events.col_status') }}</th>
                                        @if ($managingEvent->isPaid())
                                            <th class="text-left font-semibold px-3 py-2.5">{{ __('messages.admin.events.col_payment') }}</th>
                                        @endif
                                        <th class="text-center font-semibold px-3 py-2.5">{{ __('messages.admin.events.col_attendance') }}</th>
                                        <th class="px-4 py-2.5"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-line">
                                    @foreach ($managingEvent->registrations->sortByDesc('registered_at') as $reg)
                                        @php
                                            $sb = match ($reg->status) {
                                                'confirmed' => 'bg-[#15803D]/10 text-[#15803D]',
                                                'pending'   => 'bg-[#B45309]/10 text-[#B45309]',
                                                default     => 'bg-line text-faint',
                                            };
                                        @endphp
                                        <tr wire:key="reg-{{ $reg->id }}" class="{{ $reg->status === 'cancelled' ? 'opacity-50' : '' }}">
                                            <td class="px-4 py-2.5">
                                                <span class="font-semibold text-ink">{{ $reg->child?->name ?? '—' }}</span>
                                                <span class="block text-[11px] text-faint">{{ $reg->child?->parent?->name }}</span>
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <span class="inline-flex text-[11px] font-bold px-2 py-0.5 rounded-md {{ $sb }}">{{ __('messages.status.'.$reg->status) }}</span>
                                            </td>
                                            @if ($managingEvent->isPaid())
                                                <td class="px-3 py-2.5 text-muted">{{ $reg->transaction ? __('messages.status.'.$reg->transaction->status) : '—' }}</td>
                                            @endif
                                            <td class="px-3 py-2.5">
                                                @if ($reg->status === 'confirmed')
                                                    @php $att = $attendanceMap[$reg->child_id] ?? null; @endphp
                                                    <div class="flex items-center justify-center gap-1">
                                                        <button wire:click="markAttendance({{ $reg->child_id }}, 'present')"
                                                                class="text-[11px] font-semibold px-2 py-1 rounded-lg transition-colors {{ $att === 'present' ? 'bg-[#15803D] text-white' : 'bg-[#15803D]/10 text-[#15803D] hover:bg-[#15803D]/20' }}">{{ __('messages.admin.events.btn_present') }}</button>
                                                        <button wire:click="markAttendance({{ $reg->child_id }}, 'absent')"
                                                                class="text-[11px] font-semibold px-2 py-1 rounded-lg transition-colors {{ $att === 'absent' ? 'bg-[#B91C1C] text-white' : 'bg-[#B91C1C]/10 text-[#B91C1C] hover:bg-[#B91C1C]/20' }}">{{ __('messages.admin.events.btn_absent') }}</button>
                                                    </div>
                                                @else
                                                    <p class="text-center text-faint">—</p>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5 text-right">
                                                @if ($reg->status !== 'cancelled')
                                                    <x-btn variant="danger" size="sm" wire:click="cancelRegistration({{ $reg->id }})"
                                                           wire:confirm="Cancel this registration?" wire:loading.attr="disabled">{{ __('messages.common.cancel') }}</x-btn>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        </div>{{-- /max-w-6xl --}}

    @endif
</div>
