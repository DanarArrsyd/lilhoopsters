@props(['transactions', 'pendingAmount'])

<x-card class="mb-4" x-data="{ open: false }">
    <div class="flex items-center justify-between">
        <p class="text-xs font-bold uppercase tracking-wide text-muted">{{ __('messages.portal.home.payments') }}</p>
        @if ($pendingAmount > 0)
            <x-badge status="pending">{{ __('messages.portal.home.pending') }}</x-badge>
        @else
            <x-badge status="paid">{{ __('messages.portal.home.up_to_date') }}</x-badge>
        @endif
    </div>

    @if ($pendingAmount > 0)
        <p class="font-mono text-lg text-ink font-medium mt-2">
            Rp{{ number_format($pendingAmount, 0, ',', '.') }}
        </p>
        <p class="text-xs text-muted">{{ __('messages.portal.home.pending_hint') }}</p>
    @endif

    @if ($transactions->isNotEmpty())
        <button @click="open = !open" class="text-xs font-semibold text-navy mt-3 pt-3 border-t border-line block">
            <span x-show="!open">{{ __('messages.portal.home.view_history') }}</span>
            <span x-show="open" x-cloak>{{ __('messages.portal.home.hide_history') }}</span>
        </button>
        <div x-show="open" x-collapse x-cloak class="mt-3 border-t border-line">
            @foreach ($transactions as $trx)
                <div class="flex items-center justify-between py-2 border-b border-line last:border-b-0 text-sm">
                    <span class="text-ink">{{ $trx->package?->name ?? __('messages.portal.home.transaction') }}</span>
                    <span class="flex items-center gap-2">
                        <span class="font-mono text-muted">Rp{{ number_format($trx->amount, 0, ',', '.') }}</span>
                        <x-badge :status="$trx->status">{{ __('messages.status.'.$trx->status) }}</x-badge>
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-xs text-faint mt-3 pt-3 border-t border-line">{{ __('messages.portal.home.no_history') }}</p>
    @endif
</x-card>
