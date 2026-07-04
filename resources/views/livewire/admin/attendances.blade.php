<div>
    <div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.admin.attendances.title')" :subtitle="__('messages.admin.attendances.subtitle')" />

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- View toggle: Students / Coaches --}}
    <div class="flex gap-1 mb-5 bg-off border border-line rounded-xl p-1 w-fit">
        <button wire:click="$set('activeTab', 'students')"
                @class([
                    'px-5 py-1.5 rounded-lg text-sm font-semibold transition-colors',
                    'bg-navy text-off shadow-sm'        => $activeTab === 'students',
                    'text-muted hover:text-ink'         => $activeTab !== 'students',
                ])>Students</button>
        <button wire:click="$set('activeTab', 'coaches')"
                @class([
                    'px-5 py-1.5 rounded-lg text-sm font-semibold transition-colors',
                    'bg-navy text-off shadow-sm'        => $activeTab === 'coaches',
                    'text-muted hover:text-ink'         => $activeTab !== 'coaches',
                ])>Coaches</button>
    </div>

    @if ($activeTab === 'students')
        {{-- Status tabs --}}
        <div class="flex gap-2 flex-wrap mb-4">
            @foreach ([
                ''         => __('messages.common.all'),
                'present'  => __('messages.attendance.badge.attend'),
                'no_show'  => __('messages.attendance.badge.no_show'),
                'sick'     => __('messages.attendance.badge.sick'),
                'permit'   => __('messages.attendance.badge.permit'),
                'make_up'  => __('messages.admin.attendances.filter_makeup'),
            ] as $val => $label)
                <button wire:click="$set('filterStatus', '{{ $val }}')"
                        @class([
                            'px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                            'bg-navy text-off border-navy'                 => $filterStatus === $val,
                            'bg-surface text-ink border-line hover:bg-off' => $filterStatus !== $val,
                        ])>{{ $label }}</button>
            @endforeach
        </div>
    @endif

    {{-- Shared filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <x-input wire:model.live.debounce.300ms="search" class="sm:w-64"
                     :placeholder="$activeTab === 'coaches' ? 'Search by coach name...' : 'Search by child name...'" />
            <div class="flex items-center gap-2">
                <x-input type="date" wire:model.live="filterDateFrom" class="sm:w-40" />
                <span class="text-faint text-xs shrink-0">–</span>
                <x-input type="date" wire:model.live="filterDateTo" class="sm:w-40" />
                <x-select wire:model.live="filterSchedule" class="sm:w-44">
                    <option value="">{{ __('messages.admin.attendances.all_schedules') }}</option>
                    @foreach ($schedules as $s)
                        <option value="{{ $s->id }}">{{ $s->program->name }} – {{ ucfirst($s->day_of_week) }}</option>
                    @endforeach
                </x-select>
            </div>
        </div>
        <x-btn variant="secondary" wire:click="export" wire:loading.attr="disabled" wire:target="export"
               class="!px-2.5 shrink-0" title="{{ __('messages.common.export_excel') }}" aria-label="{{ __('messages.common.export_excel') }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
            </svg>
        </x-btn>
    </div>

    @if ($activeTab === 'students')
        {{-- Students table --}}
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_child') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_schedule') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_date') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_status') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_source') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_coach') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_ip') }}</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($attendances as $a)
                            <tr class="hover:bg-off transition-colors">
                                <td class="py-3 px-4 font-semibold text-ink">{{ $a->child?->name ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    @if ($a->schedule)
                                        <p class="text-ink">{{ $a->schedule->program?->name ?? __('messages.admin.schedules.type_private') }}</p>
                                        <p class="text-xs text-faint">{{ $a->schedule->location->name }} · {{ ucfirst($a->schedule->day_of_week) }}</p>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-ink">
                                    {{ $a->attended_at?->format('d M Y') }}
                                    <span class="block text-xs text-faint tabular-nums">{{ $a->created_at?->format('H:i') }} WIB</span>
                                </td>
                                <td class="py-3 px-4">
                                    <x-badge :status="$a->status">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</x-badge>
                                    @if ($a->isFlagged())
                                        <span class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-[#B45309] bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @if ($a->isLocationFlagged()){{ __('messages.admin.attendances.flag_location', ['m' => $a->distanceMeters()]) }}@else{{ __('messages.admin.attendances.flag_time') }}@endif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-muted capitalize text-xs">{{ $a->source }}</td>
                                <td class="py-3 px-4 text-muted text-xs">{{ $a->coach?->user?->name ?? '—' }}</td>
                                <td class="py-3 px-4 text-faint text-xs tabular-nums">{{ $a->ip_address ?? '—' }}</td>
                                <td class="py-3 px-4 text-right">
                                    <x-btn variant="edit" size="sm" wire:click="openOverride({{ $a->id }})" wire:loading.attr="disabled">
                                        {{ __('messages.common.override') }}
                                    </x-btn>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-2">
                                <x-empty-state :title="__('messages.admin.attendances.empty_title')" :description="__('messages.admin.attendances.empty_desc')" />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($attendances?->hasPages())
                <div class="px-4 py-3 border-t border-line">{{ $attendances->links() }}</div>
            @endif
        </x-card>

    @else
        {{-- Coaches table --}}
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Coach</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_schedule') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_date') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Check-in</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Check-out</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Duration</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.attendances.col_ip') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($coachSessions as $cs)
                            @php
                                $duration = $cs->checked_in_at && $cs->checked_out_at
                                    ? $cs->checked_in_at->diffInMinutes($cs->checked_out_at) . ' min'
                                    : null;
                            @endphp
                            <tr class="hover:bg-off transition-colors">
                                <td class="py-3 px-4 font-semibold text-ink">{{ $cs->coach?->user?->name ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    @if ($cs->schedule)
                                        <p class="text-ink">{{ $cs->schedule->program?->name ?? __('messages.admin.schedules.type_private') }}</p>
                                        <p class="text-xs text-faint">{{ $cs->schedule->location->name }} · {{ ucfirst($cs->schedule->day_of_week) }}</p>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-ink">{{ $cs->session_date?->format('d M Y') }}</td>
                                <td class="py-3 px-4 text-ink text-xs">
                                    {{ $cs->checked_in_at?->format('H:i') ?? '—' }} <span class="text-faint">WIB</span>
                                    @if ($cs->isFlagged())
                                        <span class="mt-1 inline-flex items-center gap-1 text-[10px] font-bold text-[#B45309] bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded-full">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            @if ($cs->isLocationFlagged()){{ __('messages.admin.attendances.flag_location', ['m' => $cs->distanceMeters()]) }}@else{{ __('messages.admin.attendances.flag_time') }}@endif
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-xs">
                                    @if ($cs->checked_out_at)
                                        <span class="text-ink">{{ $cs->checked_out_at->format('H:i') }} <span class="text-faint">WIB</span></span>
                                    @else
                                        <span class="text-amber-600 font-semibold">Active</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-muted text-xs">{{ $duration ?? '—' }}</td>
                                <td class="py-3 px-4 text-faint text-xs tabular-nums">{{ $cs->ip_address ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-2">
                                <x-empty-state title="No coach sessions found" description="Coach check-in records appear here." />
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($coachSessions?->hasPages())
                <div class="px-4 py-3 border-t border-line">{{ $coachSessions->links() }}</div>
            @endif
        </x-card>
    @endif

    </div>{{-- /max-w-6xl --}}

    {{-- Override Modal --}}
    @if ($showOverride)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeOverride"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.attendances.modal_title') }}</h3>
                <button wire:click="closeOverride" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model="overrideStatus" label="Status" :error="$errors->first('overrideStatus')">
                    <option value="present">{{ __('messages.admin.attendances.opt_present') }}</option>
                    <option value="no_show">{{ __('messages.admin.attendances.opt_no_show') }}</option>
                    <option value="sick">{{ __('messages.admin.attendances.opt_sick') }}</option>
                    <option value="permit">{{ __('messages.admin.attendances.opt_permit') }}</option>
                    <option value="make_up">{{ __('messages.admin.attendances.opt_makeup') }}</option>
                </x-select>
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.attendances.notes_label') }}</label>
                    <textarea wire:model="overrideNotes" rows="3" aria-label="Override notes"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="{{ __('messages.admin.attendances.notes_ph') }}"></textarea>
                    @error('overrideNotes') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeOverride">{{ __('messages.common.cancel') }}</x-btn>
                <x-btn class="flex-1" wire:click="saveOverride" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveOverride">{{ __('messages.admin.attendances.save_override') }}</span>
                    <span wire:loading wire:target="saveOverride">{{ __('messages.common.saving') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
