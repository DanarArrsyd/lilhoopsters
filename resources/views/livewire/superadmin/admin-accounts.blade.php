<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Admin Accounts</h2>
            <p class="text-sm text-muted">Manage administrator accounts for the platform.</p>
        </div>
        <x-btn wire:click="openForm">+ New Admin</x-btn>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Search --}}
    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." />
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Name</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Email</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Created</th>
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
                                    {{ $admin->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">{{ $admin->created_at->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    @if ($admin->is_active)
                                        <x-btn variant="ghost" size="sm"
                                               wire:click="confirmDeactivate({{ $admin->id }})"
                                               wire:loading.attr="disabled">Deactivate</x-btn>
                                    @else
                                        <x-btn variant="ghost" size="sm"
                                               wire:click="toggleActive({{ $admin->id }})"
                                               wire:loading.attr="disabled">Activate</x-btn>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-2">
                                <x-empty-state title="No admin accounts found" description="Add the first administrator account." />
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
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">New Admin Account</h3>
                <button wire:click="closeForm" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-input wire:model="name" label="Full Name" placeholder="Admin name"
                         required :error="$errors->first('name')" />
                <x-input type="email" wire:model="email" label="Email" placeholder="admin@example.com"
                         required :error="$errors->first('email')" />
                <x-input type="password" wire:model="password" label="Password" placeholder="Min. 8 characters"
                         required :error="$errors->first('password')" />
                <x-input type="password" wire:model="passwordConfirmation" label="Confirm Password"
                         placeholder="Repeat password" required :error="$errors->first('passwordConfirmation')" />
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeForm">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="create" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="create">Create Admin</span>
                    <span wire:loading wire:target="create">Creating...</span>
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
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Deactivate Admin</h3>
                <button wire:click="cancelDeactivate" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6">
                <p class="text-sm text-muted">This admin will no longer be able to log in. You can reactivate them at any time.</p>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelDeactivate">Cancel</x-btn>
                <x-btn variant="danger" class="flex-1" wire:click="deactivate" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="deactivate">Deactivate</span>
                    <span wire:loading wire:target="deactivate">Deactivating...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
