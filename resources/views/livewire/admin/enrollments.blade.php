<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Enrollments</h2>
            <p class="text-sm text-slate-500">Review and approve player enrollments</p>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by player name..." />
            </div>
            <div class="w-40">
                <select wire:model.live="filterType"
                        class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    <option value="">All Types</option>
                    <option value="registration">Registration</option>
                    <option value="program">Program</option>
                </select>
            </div>
            <div class="w-44">
                <select wire:model.live="filterStatus"
                        class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
                </select>
            </div>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Player</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Package</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Schedule</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Submitted</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($enrollments as $enrollment)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $enrollment->child->name }}</td>
                        <td class="py-3 px-4">
                            <x-badge status="{{ $enrollment->type === 'registration' ? 'info' : 'make_up' }}">
                                {{ ucfirst($enrollment->type) }}
                            </x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <p class="text-slate-700">{{ $enrollment->package->name }}</p>
                            <p class="text-xs text-slate-400">{{ $enrollment->package->location->name }}</p>
                        </td>
                        <td class="py-3 px-4 text-slate-500 text-xs">
                            @if ($enrollment->schedule)
                                <span class="capitalize">{{ $enrollment->schedule->day_of_week }}</span>
                                {{ substr($enrollment->schedule->start_time, 0, 5) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 px-4 text-slate-400 text-xs">{{ $enrollment->created_at->format('d M Y') }}</td>
                        <td class="py-3 px-4">
                            <x-badge :status="$enrollment->status">{{ ucfirst($enrollment->status) }}</x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2 justify-end">
                                @if ($enrollment->status === 'pending')
                                    <x-btn variant="primary" size="sm" wire:click="approve({{ $enrollment->id }})"
                                           wire:loading.attr="disabled">Approve</x-btn>
                                    <x-btn variant="danger" size="sm"
                                           wire:click="reject({{ $enrollment->id }})"
                                           wire:confirm="Reject this enrollment?"
                                           wire:loading.attr="disabled">Reject</x-btn>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-2">
                            <x-empty-state title="No enrollments found" description="No enrollments match your filters." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($enrollments->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $enrollments->links() }}
            </div>
        @endif
    </x-card>
</div>
