<div>
    <div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.admin.players.title')" :subtitle="__('messages.admin.players.subtitle')" />

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by player or parent name..." />
            </div>
            <div class="w-full sm:w-48">
                <x-select wire:model.live="filterStatus">
                    <option value="">{{ __('messages.admin.players.all_statuses') }}</option>
                    <option value="unregistered">{{ __('messages.status.unregistered') }}</option>
                    <option value="pending">{{ __('messages.status.pending') }}</option>
                    <option value="active">{{ __('messages.status.active') }}</option>
                    <option value="inactive">{{ __('messages.status.inactive') }}</option>
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
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_player') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_parent') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_age') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_gender') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_jersey') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.players.col_status') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($players as $player)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 bg-navy/8 text-navy">
                                        {{ strtoupper(substr($player->name, 0, 1)) }}
                                    </div>
                                    <p class="font-semibold text-ink">{{ $player->name }}</p>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $player->parent->name }}</td>
                            <td class="py-3 px-4 text-ink">
                                @php $months = $player->ageInMonths(); @endphp
                                @if ($months >= 12)
                                    {{ floor($months / 12) }}yr {{ $months % 12 > 0 ? ($months % 12) . 'mo' : '' }}
                                @else
                                    {{ $months }}mo
                                @endif
                            </td>
                            <td class="py-3 px-4 text-muted">{{ ucfirst($player->gender) }}</td>
                            <td class="py-3 px-4 text-muted text-xs">
                                @if ($player->jersey_name)
                                    <span class="font-semibold text-ink">{{ $player->jersey_name }}</span>
                                    @if ($player->jersey_number)
                                        <span class="ml-1 text-faint">#{{ $player->jersey_number }}</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$player->status">{{ __('messages.status.'.$player->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <x-btn variant="secondary" size="sm" wire:click="openLtv({{ $player->id }})">
                                    View
                                </x-btn>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-2">
                                <x-empty-state :title="__('messages.admin.players.empty_title')" :description="__('messages.admin.players.empty_desc')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($players->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $players->links() }}
            </div>
        @endif
    </x-card>

    @if ($showLtv)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeLtv"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <div>
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $ltvChild?->name }}</h3>
                    <p class="text-xs text-muted">{{ $ltvChild?->parent?->name }}</p>
                </div>
                <button wire:click="closeLtv" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>

            <div class="px-6 py-4 border-b border-line">
                <p class="text-2xs font-semibold text-muted uppercase tracking-wide leading-tight">Total Lifetime Spend</p>
                <p class="text-xl font-extrabold text-navy leading-none tracking-tight mt-1.5">Rp {{ number_format($ltvTotal, 0, ',', '.') }}</p>
            </div>

            <div class="p-6">
                <p class="text-3xs font-bold uppercase tracking-widest text-faint mb-3">Transaction History</p>
                @if ($ltvTransactions->isEmpty())
                    <p class="text-center text-sm text-muted py-6">No transactions yet</p>
                @else
                    <div class="divide-y divide-line">
                        @foreach ($ltvTransactions as $t)
                            <div class="py-3 flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-ink truncate">{{ $t->package?->name ?? '—' }}</p>
                                    <p class="text-2xs text-faint">{{ $t->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="text-right shrink-0">
                                    <p class="text-sm font-bold text-navy">Rp {{ number_format($t->amount, 0, ',', '.') }}</p>
                                    <x-badge :status="$t->status">{{ ucfirst($t->status) }}</x-badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    </div>{{-- /max-w-6xl --}}
</div>
