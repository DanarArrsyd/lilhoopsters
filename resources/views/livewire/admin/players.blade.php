<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.players.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.admin.players.subtitle') }}</p>
    </div>

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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
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
</div>
