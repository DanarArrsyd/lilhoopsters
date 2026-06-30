<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.parents.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.admin.parents.subtitle') }}</p>
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
                    <option value="">{{ __('messages.admin.parents.all_statuses') }}</option>
                    <option value="pending">{{ __('messages.status.pending') }}</option>
                    <option value="approved">{{ __('messages.status.approved') }}</option>
                    <option value="rejected">{{ __('messages.status.rejected') }}</option>
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
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.parents.col_parent') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.parents.col_whatsapp') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.parents.col_players') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.parents.col_registered') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.parents.col_status') }}</th>
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
                                        <p class="font-semibold text-ink">{{ $parent->name }}</p>
                                        <p class="text-xs text-faint">{{ $parent->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $parent->whatsapp_number ?? '—' }}</td>
                            <td class="py-3 px-4 text-ink">{{ $parent->children_count }}</td>
                            <td class="py-3 px-4 text-faint text-xs">{{ $parent->created_at->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$parent->registration_status">
                                    {{ __('messages.status.'.$parent->registration_status) }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    @if ($parent->registration_status === 'pending')
                                        <x-btn variant="success" size="sm" wire:click="approve({{ $parent->id }})"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            {{ __('messages.common.approve') }}
                                        </x-btn>
                                        <x-btn variant="danger" size="sm" wire:click="reject({{ $parent->id }})"
                                               wire:confirm="Reject this registration?"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                            {{ __('messages.common.reject') }}
                                        </x-btn>
                                    @elseif ($parent->registration_status === 'approved')
                                        <x-btn variant="purple" size="sm" wire:click="reject({{ $parent->id }})"
                                               wire:confirm="Revoke this approval?"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            {{ __('messages.common.revoke') }}
                                        </x-btn>
                                    @elseif ($parent->registration_status === 'rejected')
                                        <x-btn variant="success" size="sm" wire:click="approve({{ $parent->id }})"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            {{ __('messages.admin.parents.re_approve') }}
                                        </x-btn>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state :title="__('messages.admin.parents.empty_title')" :description="__('messages.admin.parents.empty_desc')" />
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
