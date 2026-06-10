<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Leave Requests</h2>
        <p class="text-sm text-muted">Review and approve parent-submitted leave requests.</p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Status tabs --}}
    <div class="flex gap-2 flex-wrap mb-4">
        @foreach ([
            'pending'       => ['label' => 'Pending',       'count' => $counts['pending']],
            'approved'      => ['label' => 'Approved',      'count' => $counts['approved']],
            'auto_approved' => ['label' => 'Auto-Approved', 'count' => $counts['auto_approved']],
            'rejected'      => ['label' => 'Rejected',      'count' => $counts['rejected']],
            ''              => ['label' => 'All',           'count' => null],
        ] as $val => $tab)
            <button wire:click="$set('filterStatus', '{{ $val }}')"
                    class="px-4 py-2 rounded-xl text-sm font-semibold border transition-colors
                        {{ $filterStatus === $val
                            ? 'bg-navy text-off border-navy'
                            : 'bg-surface text-ink border-line hover:bg-off' }}">
                {{ $tab['label'] }}
                @if ($tab['count'] !== null)
                    <span class="ml-1 text-xs {{ $filterStatus === $val ? 'opacity-70' : 'text-muted' }}">
                        {{ $tab['count'] }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Search --}}
    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search by child name..." />
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Child</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Schedule</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Leave Date</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Type</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Auto-Approve</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($requests as $req)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $req->child?->name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if ($req->schedule)
                                    <p class="text-ink">{{ $req->schedule->program->name }}</p>
                                    <p class="text-xs text-faint">{{ ucfirst($req->schedule->day_of_week) }}</p>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-ink">{{ $req->leave_date?->format('d M Y') }}</td>
                            <td class="py-3 px-4 capitalize text-ink">{{ $req->type }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$req->status">{{ ucfirst(str_replace('_', ' ', $req->status)) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">
                                {{ $req->auto_approve_at?->format('d M Y H:i') ?? '—' }}
                            </td>
                            <td class="py-3 px-4">
                                @if ($req->status === 'pending')
                                    <div class="flex items-center gap-2 justify-end">
                                        <x-btn variant="primary" size="sm"
                                               wire:click="openReview({{ $req->id }}, 'approve')"
                                               wire:loading.attr="disabled">Approve</x-btn>
                                        <x-btn variant="ghost" size="sm"
                                               wire:click="openReview({{ $req->id }}, 'reject')"
                                               wire:loading.attr="disabled">Reject</x-btn>
                                    </div>
                                @else
                                    <span class="text-faint text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-2">
                                <x-empty-state title="No leave requests found" description="No requests match the current filter." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $requests->links() }}
            </div>
        @endif
    </x-card>

    {{-- Review Modal --}}
    @if ($showReview)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeReview"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">
                    {{ $reviewAction === 'approve' ? 'Approve' : 'Reject' }} Leave Request
                </h3>
                <button wire:click="closeReview" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Admin Notes (optional)</label>
                    <textarea wire:model="adminNotes" rows="3" aria-label="Admin notes for leave request"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="Leave a note..."></textarea>
                    @error('adminNotes') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeReview">Cancel</x-btn>
                <x-btn variant="{{ $reviewAction === 'approve' ? 'primary' : 'danger' }}" class="flex-1"
                       wire:click="saveReview" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveReview">
                        {{ $reviewAction === 'approve' ? 'Approve' : 'Reject' }}
                    </span>
                    <span wire:loading wire:target="saveReview">Saving...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
