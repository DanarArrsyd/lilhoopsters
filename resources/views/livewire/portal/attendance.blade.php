<div>
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Attendance History</h2>
        <p class="text-sm text-slate-500">Track your players' session attendance</p>
    </div>

    {{-- Per-child summary cards --}}
    @if (!empty($stats))
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            @foreach ($stats as $childId => $s)
                <x-card padding="p-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="font-semibold text-slate-900 text-sm">{{ $s['name'] }}</p>
                        @if ($s['rate'] !== null)
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full
                                {{ $s['rate'] >= 80 ? 'bg-green-100 text-green-700' : ($s['rate'] >= 60 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-600') }}">
                                {{ $s['rate'] }}% present
                            </span>
                        @endif
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-xl font-bold text-slate-900">{{ $s['present'] }}</p>
                            <p class="text-xs text-slate-400">Present</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-slate-900">{{ $s['leave'] }}</p>
                            <p class="text-xs text-slate-400">Leave</p>
                        </div>
                        <div>
                            <p class="text-xl font-bold text-slate-900">{{ $s['absent'] }}</p>
                            <p class="text-xs text-slate-400">No-show</p>
                        </div>
                    </div>
                    @if ($s['total'] > 0)
                        <div class="mt-3 h-1.5 bg-slate-100 rounded-full overflow-hidden flex">
                            @if ($s['present'] > 0)
                                <div class="h-full bg-green-400" style="width: {{ ($s['present'] / $s['total']) * 100 }}%"></div>
                            @endif
                            @if ($s['leave'] > 0)
                                <div class="h-full bg-blue-400" style="width: {{ ($s['leave'] / $s['total']) * 100 }}%"></div>
                            @endif
                            @if ($s['absent'] > 0)
                                <div class="h-full bg-red-400" style="width: {{ ($s['absent'] / $s['total']) * 100 }}%"></div>
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
                <select wire:model.live="filterChildId"
                        class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    <option value="">All Players</option>
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}">{{ $child->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <select wire:model.live="filterStatus"
                        class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                    <option value="">All Statuses</option>
                    <option value="present">Present</option>
                    <option value="no_show">No Show</option>
                    <option value="sick">Sick</option>
                    <option value="permit">Permit</option>
                    <option value="make_up">Make Up</option>
                </select>
            </div>
        </div>
    </x-card>

    {{-- Attendance table --}}
    <x-card>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Player</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Program</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Notes</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($attendances as $att)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <p class="font-medium text-slate-700">{{ $att->attended_at->format('d M Y') }}</p>
                            <p class="text-xs text-slate-400">{{ $att->attended_at->format('H:i') }}</p>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-900">{{ $att->child->name }}</td>
                        <td class="py-3 px-4">
                            <p class="text-slate-700">{{ $att->schedule->program->name ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ $att->schedule->location->name ?? '' }}</p>
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $statusColors = [
                                    'present' => 'bg-green-100 text-green-700',
                                    'no_show' => 'bg-red-100 text-red-600',
                                    'sick'    => 'bg-amber-100 text-amber-700',
                                    'permit'  => 'bg-blue-100 text-blue-600',
                                    'make_up' => 'bg-purple-100 text-purple-600',
                                ];
                            @endphp
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $statusColors[$att->status] ?? 'bg-slate-100 text-slate-600' }}">
                                {{ ucwords(str_replace('_', ' ', $att->status)) }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-400">{{ $att->notes ?? '—' }}</td>
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
        @if ($attendances->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $attendances->links() }}
            </div>
        @endif
    </x-card>
</div>
