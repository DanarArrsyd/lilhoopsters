<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Make-Up Classes</h2>
            <p class="text-sm text-muted">Request a replacement session for an approved leave.</p>
        </div>
        @if ($approvedLeaves->isNotEmpty())
            <x-btn wire:click="openForm">Request Make-Up</x-btn>
        @endif
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Table --}}
    @if ($makeUpClasses->isEmpty())
        <x-empty-state title="No make-up class requests yet" description="Request a replacement session from an approved leave." />
    @else
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[500px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Child</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Target Schedule</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Target Date</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
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
                                    <x-badge :status="$mu->status">{{ ucfirst($mu->status) }}</x-badge>
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
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Request Make-Up Class</h3>
                <button wire:click="closeForm" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model="leaveRequestId" label="Approved Leave"
                          :error="$errors->first('leaveRequestId')">
                    <option value="">— Select approved leave —</option>
                    @foreach ($approvedLeaves as $lr)
                        <option value="{{ $lr->id }}">
                            {{ $lr->child->name }} · {{ $lr->leave_date->format('d M Y') }} ({{ $lr->type }})
                        </option>
                    @endforeach
                </x-select>

                <x-select wire:model="targetScheduleId" label="Target Schedule"
                          :error="$errors->first('targetScheduleId')">
                    <option value="">— Select schedule —</option>
                    @foreach ($schedules as $s)
                        <option value="{{ $s->id }}">
                            {{ $s->program->name }} · {{ ucfirst($s->day_of_week) }} · {{ $s->location->name }}
                        </option>
                    @endforeach
                </x-select>

                <x-input type="date" wire:model="targetDate" label="Target Date"
                         required :error="$errors->first('targetDate')" />
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeForm">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Submit Request</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
