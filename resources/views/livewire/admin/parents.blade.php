<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Parents</h2>
        <p class="text-sm text-muted">Review and approve parent registrations.</p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." />
            </div>
            <div class="w-full sm:w-48">
                <x-select wire:model.live="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
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
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Parent</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">WhatsApp</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Players</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Registered</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($parents as $parent)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 bg-navy/8 text-navy">
                                        {{ strtoupper(substr($parent->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-ink">{{ $parent->name }}</p>
                                        <p class="text-xs text-faint">{{ $parent->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $parent->whatsapp_number ?? '—' }}</td>
                            <td class="py-3 px-4 text-ink">{{ $parent->children_count }}</td>
                            <td class="py-3 px-4 text-faint text-xs">{{ $parent->created_at->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$parent->registration_status">
                                    {{ ucfirst($parent->registration_status) }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    @if ($parent->registration_status === 'pending')
                                        <x-btn variant="primary" size="sm" wire:click="approve({{ $parent->id }})"
                                               wire:loading.attr="disabled">Approve</x-btn>
                                        <x-btn variant="danger" size="sm" wire:click="reject({{ $parent->id }})"
                                               wire:confirm="Reject this registration?"
                                               wire:loading.attr="disabled">Reject</x-btn>
                                    @elseif ($parent->registration_status === 'approved')
                                        <x-btn variant="ghost" size="sm" wire:click="reject({{ $parent->id }})"
                                               wire:confirm="Revoke this approval?">Revoke</x-btn>
                                    @elseif ($parent->registration_status === 'rejected')
                                        <x-btn variant="ghost" size="sm" wire:click="approve({{ $parent->id }})">Re-approve</x-btn>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state title="No parents found" description="No parent accounts match your search." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($parents->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $parents->links() }}</div>
        @endif
    </x-card>
</div>
