<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.pages.makeup.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.pages.makeup.subtitle') }}</p>
    </div>
    @if ($approvedLeaves->isNotEmpty())
        <button wire:click="openForm"
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

    {{-- Table --}}
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

    {{-- Request Form Modal --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeForm"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.makeup.form_title') }}</h3>
                <button wire:click="closeForm" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model="leaveRequestId" :label="__('messages.makeup.approved_leave')"
                          :error="$errors->first('leaveRequestId')">
                    <option value="">{{ __('messages.makeup.select_leave') }}</option>
                    @foreach ($approvedLeaves as $lr)
                        <option value="{{ $lr->id }}">
                            {{ $lr->child->name }} · {{ $lr->leave_date->format('d M Y') }} ({{ $lr->type }})
                        </option>
                    @endforeach
                </x-select>

                <x-select wire:model="targetScheduleId" :label="__('messages.makeup.col_schedule')"
                          :error="$errors->first('targetScheduleId')">
                    <option value="">{{ __('messages.makeup.select_schedule') }}</option>
                    @foreach ($schedules as $s)
                        <option value="{{ $s->id }}">
                            {{ $s->program->name }} · {{ ucfirst($s->day_of_week) }} · {{ $s->location->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-input type="date" wire:model="targetDate" :label="__('messages.makeup.col_date')"
                         required :error="$errors->first('targetDate')" />
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeForm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ __('messages.common.cancel') }}
                </x-btn>
                <x-btn class="flex-1" wire:click="submit" wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span wire:loading.remove wire:target="submit">{{ __('messages.makeup.submit') }}</span>
                    <span wire:loading wire:target="submit">{{ __('messages.common.submitting') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
