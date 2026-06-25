<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.attendances.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.admin.attendances.subtitle') }}</p>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

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
                        'bg-navy text-off border-navy'                            => $filterStatus === $val,
                        'bg-surface text-ink border-line hover:bg-off'            => $filterStatus !== $val,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Inline filters --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search by child name..." />
        <x-input type="date" wire:model.live="filterDate" />
        <x-select wire:model.live="filterSchedule">
            <option value="">{{ __('messages.admin.attendances.all_schedules') }}</option>
            @foreach ($schedules as $s)
                <option value="{{ $s->id }}">{{ $s->program->name }} – {{ ucfirst($s->day_of_week) }}</option>
            @endforeach
        </x-select>
    </div>

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
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($attendances as $a)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $a->child?->name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if ($a->schedule)
                                    <p class="text-ink">{{ $a->schedule->program->name }}</p>
                                    <p class="text-xs text-faint">{{ $a->schedule->location->name }} · {{ ucfirst($a->schedule->day_of_week) }}</p>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-ink">{{ $a->attended_at?->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$a->status">{{ ucfirst(str_replace('_', ' ', $a->status)) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-muted capitalize text-xs">{{ $a->source }}</td>
                            <td class="py-3 px-4 text-muted text-xs">{{ $a->coach?->user?->name ?? '—' }}</td>
                            <td class="py-3 px-4 text-right">
                                <x-btn variant="edit" size="sm" wire:click="openOverride({{ $a->id }})" wire:loading.attr="disabled">
                                    {{ __('messages.common.override') }}
                                </x-btn>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-2">
                            <x-empty-state :title="__('messages.admin.attendances.empty_title')" :description="__('messages.admin.attendances.empty_desc')" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendances->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $attendances->links() }}</div>
        @endif
    </x-card>

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
