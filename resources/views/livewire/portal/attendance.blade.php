<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Attendance History</h2>
        <p class="text-sm text-muted">Track your players' session attendance.</p>
    </div>

    {{-- Per-child summary cards --}}
    @if (!empty($stats))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @foreach ($stats as $childId => $s)
                <x-card padding="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-semibold text-ink text-sm">{{ $s['name'] }}</p>
                        @if ($s['rate'] !== null)
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                {{ $s['rate'] >= 80 ? 'bg-[#15803D]/10 text-[#15803D]' : ($s['rate'] >= 60 ? 'bg-[#B45309]/10 text-[#B45309]' : 'bg-[#B91C1C]/10 text-[#B91C1C]') }}">
                                {{ $s['rate'] }}% present
                            </span>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-xl font-extrabold text-[#15803D]">{{ $s['present'] }}</p>
                            <p class="text-xs text-faint">Present</p>
                        </div>
                        <div>
                            <p class="text-xl font-extrabold text-[#1D4ED8]">{{ $s['leave'] }}</p>
                            <p class="text-xs text-faint">Leave</p>
                        </div>
                        <div>
                            <p class="text-xl font-extrabold text-[#B91C1C]">{{ $s['absent'] }}</p>
                            <p class="text-xs text-faint">No-show</p>
                        </div>
                    </div>
                    @if ($s['total'] > 0)
                        <div class="mt-3 h-1.5 bg-line rounded-full overflow-hidden flex">
                            @if ($s['present'] > 0)
                                <div class="h-full bg-[#15803D]" style="width: {{ ($s['present'] / $s['total']) * 100 }}%"></div>
                            @endif
                            @if ($s['leave'] > 0)
                                <div class="h-full bg-[#1D4ED8]" style="width: {{ ($s['leave'] / $s['total']) * 100 }}%"></div>
                            @endif
                            @if ($s['absent'] > 0)
                                <div class="h-full bg-[#B91C1C]" style="width: {{ ($s['absent'] / $s['total']) * 100 }}%"></div>
                            @endif
                        </div>
                    @endif
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex gap-3 flex-wrap">
            <div class="w-44">
                <x-select wire:model.live="filterChildId">
                    <option value="">All Players</option>
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}">{{ $child->name }}</option>
                    @endforeach
                </x-select>
            </div>
            <div class="w-40">
                <x-select wire:model.live="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="present">Present</option>
                    <option value="no_show">No Show</option>
                    <option value="sick">Sick</option>
                    <option value="permit">Permit</option>
                    <option value="make_up">Make Up</option>
                </x-select>
            </div>
        </div>
    </x-card>

    {{-- Attendance table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[560px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Date</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Player</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Program</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($attendances as $att)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $att->attended_at->format('d M Y') }}</p>
                                <p class="text-xs text-faint">{{ $att->attended_at->format('H:i') }}</p>
                            </td>
                            <td class="py-3 px-4 font-semibold text-ink">{{ $att->child->name }}</td>
                            <td class="py-3 px-4">
                                <p class="text-ink">{{ $att->schedule->program->name ?? '—' }}</p>
                                <p class="text-xs text-faint">{{ $att->schedule->location->name ?? '' }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$att->status">{{ ucwords(str_replace('_', ' ', $att->status)) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">{{ $att->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-2">
                                <x-empty-state title="No attendance records" description="Attendance will appear here once sessions are recorded." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($attendances->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $attendances->links() }}
            </div>
        @endif
    </x-card>
</div>
