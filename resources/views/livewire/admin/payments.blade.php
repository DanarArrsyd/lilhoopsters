<div>
    <div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.admin.payments.title')" :subtitle="__('messages.admin.payments.subtitle')" />

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Status tabs --}}
    <div class="flex gap-2 flex-wrap mb-4">
        @foreach ([
            ''         => __('messages.common.all'),
            'pending'  => __('messages.status.pending'),
            'paid'     => __('messages.status.paid'),
            'rejected' => __('messages.status.rejected'),
            'expired'  => __('messages.status.expired'),
        ] as $val => $label)
            <button wire:click="$set('filterStatus', '{{ $val }}')"
                    @class([
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                        'bg-navy text-off border-navy'                 => $filterStatus === $val,
                        'bg-surface text-ink border-line hover:bg-off' => $filterStatus !== $val,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search by code or parent name..." />
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.payments.col_transaction') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.payments.col_parent_player') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.payments.col_package') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.payments.col_amount') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.payments.col_proof') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.payments.col_status') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($transactions as $trx)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <p class="tabular-nums text-xs font-semibold text-ink">{{ $trx->transaction_code }}</p>
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
                                    <a href="{{ Storage::disk('public')->url($trx->payment_proof) }}" target="_blank"
                                       class="text-navy hover:underline text-xs font-semibold">{{ __('messages.admin.payments.view_proof') }}</a>
                                @else
                                    <span class="text-faint text-xs">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$trx->status">{{ __('messages.status.'.$trx->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($trx->status === 'pending')
                                    <div class="flex items-center gap-2 justify-end">
                                        <x-btn variant="success" size="sm" wire:click="verify({{ $trx->id }})" wire:loading.attr="disabled">{{ __('messages.common.verify') }}</x-btn>
                                        <x-btn variant="danger" size="sm" wire:click="reject({{ $trx->id }})" wire:loading.attr="disabled">{{ __('messages.common.reject') }}</x-btn>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-2">
                            <x-empty-state :title="__('messages.admin.payments.empty_title')" :description="__('messages.admin.payments.empty_desc')" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $transactions->links() }}</div>
        @endif
    </x-card>

    </div>{{-- /max-w-6xl --}}

    {{-- Reject modal --}}
    @if ($rejectingId)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.payments.modal_reject_title') }}</h3>
                <button wire:click="cancelReject" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <p class="text-sm text-muted">{{ __('messages.admin.payments.reject_note_label') }}</p>
                <textarea wire:model="adminNote" rows="3" aria-label="Rejection note"
                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                          placeholder="e.g. Payment proof unclear, please re-upload..."></textarea>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelReject">{{ __('messages.common.cancel') }}</x-btn>
                <x-btn variant="danger" class="flex-1" wire:click="confirmReject" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="confirmReject">{{ __('messages.admin.payments.confirm_reject') }}</span>
                    <span wire:loading wire:target="confirmReject">{{ __('messages.admin.payments.rejecting') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
