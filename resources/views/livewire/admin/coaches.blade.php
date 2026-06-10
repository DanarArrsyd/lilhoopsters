<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Coaches</h2>
            <p class="text-sm text-muted">Manage coach accounts and location assignments.</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Coach</x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." />
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Coach</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Phone</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Specialization</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Locations</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($coaches as $coach)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 bg-navy/8 text-navy">
                                        {{ strtoupper(substr($coach->user->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-ink">{{ $coach->user->name }}</p>
                                        <p class="text-xs text-faint">{{ $coach->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $coach->phone }}</td>
                            <td class="py-3 px-4 text-muted">{{ $coach->specialization ?? '—' }}</td>
                            <td class="py-3 px-4">
                                @forelse ($coach->locations as $loc)
                                    <span class="inline-flex items-center bg-navy/8 text-navy text-[11px] font-semibold px-2 py-0.5 rounded-full mr-1">
                                        {{ $loc->name }}
                                    </span>
                                @empty
                                    <span class="text-faint text-xs">—</span>
                                @endforelse
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$coach->is_active ? 'active' : 'inactive'">
                                    {{ $coach->is_active ? 'Active' : 'Inactive' }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="ghost" size="sm"
                                        wire:click="openEdit({{ $coach->id }})"
                                        wire:loading.attr="disabled">Edit</x-btn>
                                    <x-btn variant="ghost" size="sm"
                                        wire:click="toggleActive({{ $coach->id }})"
                                        wire:loading.attr="disabled">
                                        {{ $coach->is_active ? 'Deactivate' : 'Activate' }}
                                    </x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state title="No coaches yet" description="Add your first coach account." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($coaches->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $coaches->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? 'Edit Coach' : 'New Coach' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="coach_name" label="Full Name" placeholder="Coach name"
                             required :error="$errors->first('coach_name')" />
                    <x-input wire:model="coach_email" type="email" label="Email" placeholder="coach@example.com"
                             required :error="$errors->first('coach_email')" />
                    <x-input wire:model="coach_password" type="password"
                             label="{{ $editingId ? 'New Password' : 'Password' }}"
                             placeholder="{{ $editingId ? 'Leave blank to keep current' : 'Min 8 characters' }}"
                             :required="!$editingId" :error="$errors->first('coach_password')" />
                    <x-input wire:model="phone" label="Phone / WhatsApp" placeholder="081234567890"
                             required :error="$errors->first('phone')" />
                    <x-input wire:model="specialization" label="Specialization"
                             placeholder="e.g. Dribbling, Defense"
                             :error="$errors->first('specialization')" />

                    @if ($locations->count() > 0)
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Assigned Locations</label>
                            <div class="space-y-1.5 border border-line rounded-xl p-3">
                                @foreach ($locations as $loc)
                                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                                        <input type="checkbox" wire:model="selectedLocations" value="{{ $loc->id }}" class="rounded accent-navy">
                                        {{ $loc->name }}
                                        <span class="text-xs text-faint">{{ $loc->city }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded accent-navy"> Active
                    </label>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1" wire:click="$set('showModal', false)">Cancel</x-btn>
                    <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
