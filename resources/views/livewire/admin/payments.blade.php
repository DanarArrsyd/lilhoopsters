<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Payments</h2>
        <p class="text-sm text-muted">Review and verify incoming payments.</p>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by code or parent name..." />
            </div>
            <div class="w-full sm:w-44">
                <x-select wire:model.live="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="rejected">Rejected</option>
                    <option value="expired">Expired</option>
                </x-select>
            </div>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Transaction</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Parent / Player</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Package</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Amount</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Proof</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($transactions as $trx)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <p class="font-mono text-xs font-semibold text-ink">{{ $trx->transaction_code }}</p>
                                <p class="text-xs text-faint">{{ $trx->created_at->format('d M Y') }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $trx->user?->name ?? '—' }}</p>
                                <p class="text-xs text-faint">{{ $trx->child?->name ?? '—' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <p class="text-ink">{{ $trx->package?->name ?? '—' }}</p>
                                <p class="text-xs text-faint">{{ $trx->package?->location?->name ?? '' }}</p>
                            </td>
                            <td class="py-3 px-4 font-semibold text-ink">
                                Rp {{ number_format($trx->amount, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-4">
                                @if ($trx->payment_proof)
                                    <a href="{{ Storage::url($trx->payment_proof) }}" target="_blank"
                                       class="text-navy hover:underline text-xs font-semibold">
                                        View Proof
                                    </a>
                                @else
                                    <span class="text-faint text-xs">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$trx->status">{{ ucfirst($trx->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    @if ($trx->status === 'pending')
                                        <x-btn variant="primary" size="sm" wire:click="verify({{ $trx->id }})"
                                               wire:loading.attr="disabled">Verify</x-btn>
                                        <x-btn variant="danger" size="sm" wire:click="reject({{ $trx->id }})"
                                               wire:loading.attr="disabled">Reject</x-btn>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-2">
                                <x-empty-state title="No payments found" description="No transactions match your filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $transactions->links() }}</div>
        @endif
    </x-card>

    {{-- Reject modal --}}
    @if ($rejectingId)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Reject Payment</h3>
                <button wire:click="cancelReject" class="text-muted hover:text-navy p-1 leading-none">✕</button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-muted">Optionally add a note for the parent:</p>
                <textarea wire:model="adminNote" rows="3"
                          aria-label="Rejection note for parent"
                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                          placeholder="e.g. Payment proof unclear, please re-upload..."></textarea>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelReject">Cancel</x-btn>
                <x-btn variant="danger" class="flex-1" wire:click="confirmReject" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmReject">Confirm Reject</span>
                    <span wire:loading wire:target="confirmReject">Rejecting...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
