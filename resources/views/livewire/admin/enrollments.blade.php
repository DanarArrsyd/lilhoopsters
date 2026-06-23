<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Enrollments</h2>
        <p class="text-sm text-muted">Review and approve player enrollments.</p>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Status tabs --}}
    <div class="flex gap-2 flex-wrap mb-4">
        @foreach ([
            ''           => 'All',
            'pending'    => 'Pending',
            'approved'   => 'Approved',
            'rejected'   => 'Rejected',
            'expired'    => 'Expired',
        ] as $val => $label)
            <button wire:click="$set('filterStatus', '{{ $val }}')"
                    @class([
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                        'bg-navy text-off border-navy'                 => $filterStatus === $val,
                        'bg-surface text-ink border-line hover:bg-off' => $filterStatus !== $val,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Inline filters --}}
    <div class="flex gap-3 mb-4">
        <div class="flex-1">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Search by player name..." />
        </div>
        <div class="w-40 shrink-0">
            <x-select wire:model.live="filterType">
                <option value="">All Types</option>
                <option value="registration">Registration</option>
                <option value="program">Program</option>
            </x-select>
        </div>
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Player</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Type</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Package</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Schedule</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Submitted</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($enrollments as $enrollment)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $enrollment->child->name }}</td>
                            <td class="py-3 px-4">
                                <x-badge status="{{ $enrollment->type === 'registration' ? 'info' : 'make_up' }}">
                                    {{ ucfirst($enrollment->type) }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <p class="text-ink">{{ $enrollment->package->name }}</p>
                                <p class="text-xs text-faint">{{ $enrollment->package->location->name }}</p>
                            </td>
                            <td class="py-3 px-4 text-xs text-muted">
                                @if ($enrollment->schedule)
                                    <span class="capitalize">{{ $enrollment->schedule->day_of_week }}</span>
                                    {{ substr($enrollment->schedule->start_time, 0, 5) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">{{ $enrollment->created_at->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$enrollment->status">{{ ucfirst($enrollment->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($enrollment->status === 'pending')
                                    <div class="flex items-center gap-2 justify-end">
                                        <x-btn variant="success" size="sm" wire:click="approve({{ $enrollment->id }})" wire:loading.attr="disabled">Approve</x-btn>
                                        <x-btn variant="danger" size="sm" wire:click="reject({{ $enrollment->id }})"
                                               wire:confirm="Reject this enrollment?" wire:loading.attr="disabled">Reject</x-btn>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-2">
                            <x-empty-state title="No enrollments found" description="No enrollments match your filters." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($enrollments->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $enrollments->links() }}</div>
        @endif
    </x-card>
</div>
