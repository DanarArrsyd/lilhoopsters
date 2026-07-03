<div class="max-w-6xl mx-auto">

    <x-admin.page-header title="Audit Log" subtitle="Governance trail of sensitive admin, coach, and superadmin actions." />

    <x-card class="mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Search description..." />

            <x-select wire:model.live="filterAction">
                <option value="">All actions</option>
                @foreach ($actionOptions as $action)
                    <option value="{{ $action }}">{{ ucwords(str_replace(['_', '.'], [' ', ' — '], $action)) }}</option>
                @endforeach
            </x-select>

            <x-select wire:model.live="filterActorId">
                <option value="">All actors</option>
                @foreach ($actorOptions as $actor)
                    <option value="{{ $actor->actor_id }}">{{ $actor->actor_name }}</option>
                @endforeach
            </x-select>

            <div class="flex gap-2">
                <x-input type="date" wire:model.live="filterDateFrom" />
                <x-input type="date" wire:model.live="filterDateTo" />
            </div>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Waktu</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Actor</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Action</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Subject</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Description</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 text-ink whitespace-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $log->actor_name }}</p>
                                <span class="text-[10px] font-bold uppercase text-faint">{{ $log->actor_role }}</span>
                            </td>
                            <td class="py-3 px-4 text-xs">
                                <span class="bg-navy/8 text-navy px-2 py-0.5 rounded-full font-semibold">
                                    {{ ucwords(str_replace(['_', '.'], [' ', ' — '], $log->action)) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-muted text-xs">
                                @if ($log->subject_type)
                                    {{ class_basename($log->subject_type) }} #{{ $log->subject_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4 text-ink">{{ $log->description }}</td>
                            <td class="py-3 px-4 text-faint text-xs tabular-nums">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-2">
                            <x-empty-state title="No audit log entries" description="Sensitive admin/coach/superadmin actions will appear here." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $logs->links() }}</div>
        @endif
    </x-card>

</div>
