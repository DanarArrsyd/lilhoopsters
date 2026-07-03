<div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.superadmin.admin_accounts.title')" :subtitle="__('messages.superadmin.admin_accounts.subtitle')">
        <x-slot name="action">
            <x-btn-add wire:click="openForm" :label="__('messages.superadmin.admin_accounts.new')" />
        </x-slot>
    </x-admin.page-header>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Search --}}
    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('messages.superadmin.admin_accounts.search_placeholder') }}" />
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.superadmin.admin_accounts.col_name') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.superadmin.admin_accounts.col_email') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.superadmin.admin_accounts.col_status') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.superadmin.admin_accounts.col_created') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($admins as $admin)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-navy/8 text-navy flex items-center justify-center text-xs font-bold uppercase flex-shrink-0">
                                        {{ substr($admin->name, 0, 1) }}
                                    </div>
                                    <span class="font-semibold text-ink">{{ $admin->name }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $admin->email }}</td>
                            <td class="py-3 px-4">
                                <x-badge :status="$admin->is_active ? 'active' : 'inactive'">
                                    {{ $admin->is_active ? __('messages.superadmin.admin_accounts.status_active') : __('messages.superadmin.admin_accounts.status_inactive') }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">{{ $admin->created_at->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    @if ($admin->is_active)
                                        <x-btn variant="warning" size="sm"
                                               wire:click="confirmDeactivate({{ $admin->id }})"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            {{ __('messages.superadmin.admin_accounts.deactivate') }}
                                        </x-btn>
                                    @else
                                        <x-btn variant="success" size="sm"
                                               wire:click="toggleActive({{ $admin->id }})"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                            {{ __('messages.superadmin.admin_accounts.activate') }}
                                        </x-btn>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-2">
                                <x-empty-state :title="__('messages.superadmin.admin_accounts.empty_title')" :description="__('messages.superadmin.admin_accounts.empty_desc')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($admins->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $admins->links() }}
            </div>
        @endif
    </x-card>

    {{-- Create Modal --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeForm"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.superadmin.admin_accounts.modal_new_title') }}</h3>
                <button wire:click="closeForm" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-input wire:model="name" label="{{ __('messages.superadmin.admin_accounts.label_name') }}" placeholder="{{ __('messages.superadmin.admin_accounts.placeholder_name') }}"
                         required :error="$errors->first('name')" />
                <x-input type="email" wire:model="email" label="{{ __('messages.superadmin.admin_accounts.label_email') }}" placeholder="{{ __('messages.superadmin.admin_accounts.placeholder_email') }}"
                         required :error="$errors->first('email')" />
                <x-input type="password" wire:model="password" label="{{ __('messages.superadmin.admin_accounts.label_password') }}" placeholder="{{ __('messages.superadmin.admin_accounts.placeholder_password') }}"
                         required :error="$errors->first('password')" />
                <x-input type="password" wire:model="passwordConfirmation" label="{{ __('messages.superadmin.admin_accounts.label_confirm') }}"
                         placeholder="{{ __('messages.superadmin.admin_accounts.placeholder_confirm') }}" required :error="$errors->first('passwordConfirmation')" />
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeForm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ __('messages.superadmin.admin_accounts.cancel') }}
                </x-btn>
                <x-btn class="flex-1" wire:click="create" wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span wire:loading.remove wire:target="create">{{ __('messages.superadmin.admin_accounts.create') }}</span>
                    <span wire:loading wire:target="create">{{ __('messages.superadmin.admin_accounts.creating') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Deactivate Confirm Modal --}}
    @if ($showDeactivate)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="cancelDeactivate"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.superadmin.admin_accounts.deactivate_title') }}</h3>
                <button wire:click="cancelDeactivate" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6">
                <p class="text-sm text-muted">{{ __('messages.superadmin.admin_accounts.deactivate_body') }}</p>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelDeactivate">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ __('messages.superadmin.admin_accounts.cancel') }}
                </x-btn>
                <x-btn variant="danger" class="flex-1" wire:click="deactivate" wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span wire:loading.remove wire:target="deactivate">{{ __('messages.superadmin.admin_accounts.deactivate') }}</span>
                    <span wire:loading wire:target="deactivate">{{ __('messages.superadmin.admin_accounts.deactivating') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
