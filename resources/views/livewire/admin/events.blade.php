<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.events.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.admin.events.subtitle') }}</p>
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
                                           wire:click="toggleActive({{ $event->id }})" wire:loading.attr="disabled"
                                           wire:target="toggleActive({{ $event->id }})">
                                        {{ $event->is_active ? __('messages.common.deactivate') : __('messages.common.activate') }}
                                    </x-btn>
                                    <x-btn variant="danger" size="sm"
                                           wire:click="confirmDelete({{ $event->id }})"
                                           wire:confirm="Delete this event? Frozen packages will be reverted."
                                           wire:loading.attr="disabled" wire:target="confirmDelete({{ $event->id }})">
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

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-surface">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? __('messages.admin.events.modal_edit') : __('messages.admin.events.modal_new') }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="name" label="{{ __('messages.admin.events.label_name') }}" placeholder="e.g. Summer Champ"
                             required :error="$errors->first('name')" />

                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.events.label_description') }}</label>
                        <textarea wire:model="description" rows="2" aria-label="Event description"
                                  class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                  placeholder="{{ __('messages.admin.events.desc_ph') }}"></textarea>
                        @error('description') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input wire:model="start_date" type="date" label="{{ __('messages.admin.events.label_start_date') }}" required :error="$errors->first('start_date')" />
                        <x-input wire:model="end_date" type="date" label="{{ __('messages.admin.events.label_end_date') }}" required :error="$errors->first('end_date')" />
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

                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded accent-navy">
                        {{ __('messages.admin.events.label_active') }}
                    </label>

                    <p class="text-xs text-muted bg-off border border-line rounded-lg px-3 py-2">
                        {{ __('messages.admin.events.freeze_desc') }}
                    </p>

                    {{-- Registration (Phase 2A) --}}
                    <div class="border-t border-line pt-4 space-y-3" x-data>
                        <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                            <input type="checkbox" wire:model.live="is_registerable" class="rounded accent-navy">
                            {{ __('messages.admin.events.label_open_reg') }}
                        </label>

                        @if ($is_registerable)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <x-input wire:model="price" type="number" label="{{ __('messages.admin.events.label_price') }}" placeholder="0 = free"
                                         :error="$errors->first('price')" />
                                <x-input wire:model="capacity" type="number" label="{{ __('messages.admin.events.label_capacity') }}" placeholder="Blank = unlimited"
                                         :error="$errors->first('capacity')" />
                            </div>
                            <p class="text-[11px] text-faint">{{ __('messages.admin.events.free_hint') }}</p>
                        @endif
                    </div>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1" wire:click="$set('showModal', false)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('messages.common.cancel') }}
                    </x-btn>
                    <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span wire:loading.remove wire:target="save">{{ $editingId ? __('messages.common.update') : __('messages.common.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif

    {{-- Participants panel --}}
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
                    {{-- Add participant --}}
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

                    {{-- Attendance date --}}
                    <div class="flex items-center gap-2">
                        <label class="text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.events.attendance_for') }}</label>
                        <input type="date" wire:model.live="attendanceDate"
                               min="{{ $managingEvent->start_date->toDateString() }}"
                               max="{{ $managingEvent->end_date->toDateString() }}"
                               class="text-xs border border-line rounded-lg px-2.5 py-1.5 text-ink bg-off focus:outline-none focus:ring-2 focus:ring-navy/20" />
                    </div>

                    {{-- Participant list --}}
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
    @endif
</div>
