<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Payments</h2>
            <p class="text-sm text-slate-500">Review and verify incoming payments</p>
        </div>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by code or parent name..." />
            </div>
            <div class="w-44">
                <select wire:model.live="filterStatus"
                        class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
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
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Transaction</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Parent / Player</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Package</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Amount</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Proof</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($transactions as $trx)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <p class="font-mono text-xs font-semibold text-slate-700">{{ $trx->transaction_code }}</p>
                            <p class="text-xs text-slate-400">{{ $trx->created_at->format('d M Y') }}</p>
                        </td>
                        <td class="py-3 px-4">
                            <p class="font-medium text-slate-900">{{ $trx->user?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $trx->child?->name ?? '—' }}</p>
                        </td>
                        <td class="py-3 px-4">
                            <p class="text-slate-700">{{ $trx->package?->name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $trx->package?->location?->name ?? '' }}</p>
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-900">
                            Rp {{ number_format($trx->amount, 0, ',', '.') }}
                        </td>
                        <td class="py-3 px-4">
                            @if ($trx->payment_proof)
                                <a href="{{ Storage::url($trx->payment_proof) }}" target="_blank"
                                   class="text-orange-500 hover:text-orange-600 text-xs font-medium underline">
                                    View Proof
                                </a>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
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
                                    <x-btn variant="danger" size="sm" wire:click="reject({{ $trx->id }})">Reject</x-btn>
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
        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </x-card>

    {{-- Reject modal with note --}}
    @if ($rejectingId)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Reject Payment</h3>
                </div>
                <div class="p-6 space-y-4">
                    <p class="text-sm text-slate-600">Optionally add a note for the parent:</p>
                    <div class="space-y-1">
                        <textarea wire:model="adminNote" rows="3"
                                  class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                                  placeholder="e.g. Bukti transfer tidak jelas, mohon upload ulang..."></textarea>
                    </div>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1 justify-center" wire:click="cancelReject">Cancel</x-btn>
                    <x-btn variant="danger" class="flex-1 justify-center" wire:click="confirmReject"
                           wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="confirmReject">Confirm Reject</span>
                        <span wire:loading wire:target="confirmReject">Rejecting...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
