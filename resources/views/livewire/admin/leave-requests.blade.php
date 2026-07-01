<div>
    <div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.admin.leave_requests.title')" :subtitle="__('messages.admin.leave_requests.subtitle')" />

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Status tabs --}}
    <div class="flex gap-2 flex-wrap mb-4">
        @foreach ([
            'pending'       => ['label' => __('messages.admin.leave_requests.tab_pending'),  'count' => $counts['pending']],
            'approved'      => ['label' => __('messages.admin.leave_requests.tab_approved'), 'count' => $counts['approved']],
            'auto_approved' => ['label' => __('messages.admin.leave_requests.tab_auto'),     'count' => $counts['auto_approved']],
            'rejected'      => ['label' => __('messages.admin.leave_requests.tab_rejected'), 'count' => $counts['rejected']],
            ''              => ['label' => __('messages.admin.leave_requests.tab_all'),      'count' => null],
        ] as $val => $tab)
            <button wire:click="$set('filterStatus', '{{ $val }}')"
                    @class([
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                        'bg-navy text-off border-navy'                 => $filterStatus === $val,
                        'bg-surface text-ink border-line hover:bg-off' => $filterStatus !== $val,
                    ])>
                {{ $tab['label'] }}
                @if ($tab['count'])
                    <span @class(['ml-1 text-xs', 'opacity-70' => $filterStatus === $val, 'text-muted' => $filterStatus !== $val])>
                        {{ $tab['count'] }}
                    </span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('messages.admin.leave_requests.search_ph') }}" />
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leave_requests.col_child') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leave_requests.col_schedule') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leave_requests.col_date') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leave_requests.col_type') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leave_requests.col_status') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leave_requests.col_auto') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($requests as $req)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $req->child?->name ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @if ($req->schedule)
                                    <p class="text-ink">{{ $req->schedule->program->name }}</p>
                                    <p class="text-xs text-faint">{{ ucfirst($req->schedule->day_of_week) }}</p>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-ink">{{ $req->leave_date?->format('d M Y') }}</td>
                            <td class="py-3 px-4 capitalize text-ink">{{ $req->type }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$req->status">{{ __('messages.status.'.$req->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">
                                {{ $req->auto_approve_at?->format('d M Y H:i') ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($req->status === 'pending')
                                    <div class="flex items-center gap-2 justify-end">
                                        <x-btn variant="success" size="sm" wire:click="openReview({{ $req->id }}, 'approve')" wire:loading.attr="disabled">{{ __('messages.common.approve') }}</x-btn>
                                        <x-btn variant="danger" size="sm" wire:click="openReview({{ $req->id }}, 'reject')" wire:loading.attr="disabled">{{ __('messages.common.reject') }}</x-btn>
                                    </div>
                                @else
                                    <span class="text-faint text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-2">
                            <x-empty-state :title="__('messages.admin.leave_requests.empty_title')" :description="__('messages.admin.leave_requests.empty_desc')" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $requests->links() }}</div>
        @endif
    </x-card>

    </div>{{-- /max-w-6xl --}}

    {{-- Review Modal --}}
    @if ($showReview)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeReview"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">
                    {{ $reviewAction === 'approve' ? __('messages.admin.leave_requests.modal_approve') : __('messages.admin.leave_requests.modal_reject') }}
                </h3>
                <button wire:click="closeReview" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.leave_requests.admin_notes') }}</label>
                    <textarea wire:model="adminNotes" rows="3" aria-label="Admin notes"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="{{ __('messages.admin.leave_requests.notes_ph') }}"></textarea>
                    @error('adminNotes') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeReview">{{ __('messages.common.cancel') }}</x-btn>
                <x-btn variant="{{ $reviewAction === 'approve' ? 'success' : 'danger' }}" class="flex-1"
                       wire:click="saveReview" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="saveReview">{{ $reviewAction === 'approve' ? __('messages.common.approve') : __('messages.common.reject') }}</span>
                    <span wire:loading wire:target="saveReview">{{ __('messages.common.saving') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
