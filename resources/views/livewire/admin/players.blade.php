<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Players</h2>
            <p class="text-sm text-slate-500">All registered players in the academy</p>
        </div>
    </div>

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by player or parent name..." />
            </div>
            <div class="w-48">
                <select wire:model.live="filterStatus"
                        class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    <option value="">All Statuses</option>
                    <option value="unregistered">Unregistered</option>
                    <option value="pending">Pending</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
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
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Parent</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Age</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Gender</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Jersey</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($players as $player)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold shrink-0
                                    {{ $player->gender === 'male' ? 'bg-blue-100 text-blue-600' : 'bg-pink-100 text-pink-600' }}">
                                    {{ strtoupper(substr($player->name, 0, 1)) }}
                                </div>
                                <p class="font-medium text-slate-900">{{ $player->name }}</p>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-slate-500">{{ $player->parent->name }}</td>
                        <td class="py-3 px-4 text-slate-600">
                            @php $months = $player->ageInMonths(); @endphp
                            @if ($months >= 12)
                                {{ floor($months / 12) }}yr {{ $months % 12 > 0 ? ($months % 12) . 'mo' : '' }}
                            @else
                                {{ $months }}mo
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-xs font-medium {{ $player->gender === 'male' ? 'text-blue-600' : 'text-pink-600' }}">
                                {{ ucfirst($player->gender) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-slate-500 text-xs">
                            @if ($player->jersey_name)
                                <span class="font-medium text-slate-700">{{ $player->jersey_name }}</span>
                                @if ($player->jersey_number)
                                    <span class="ml-1 text-slate-400">#{{ $player->jersey_number }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <x-badge :status="$player->status">{{ ucfirst($player->status) }}</x-badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-2">
                            <x-empty-state title="No players found" description="No players match your search." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($players->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $players->links() }}
            </div>
        @endif
    </x-card>
</div>
