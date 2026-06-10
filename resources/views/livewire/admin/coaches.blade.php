<div>
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Coaches</h2>
            <p class="text-sm text-slate-500">Manage coach accounts and location assignments</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Coach</x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search by name or email..." />
    </x-card>

    <x-card>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100">
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Coach</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Phone</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Specialization</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Locations</th>
                    <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    <th class="py-3 px-4"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse ($coaches as $coach)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center text-orange-600 font-semibold text-sm shrink-0">
                                    {{ strtoupper(substr($coach->user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900">{{ $coach->user->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $coach->user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-slate-600">{{ $coach->phone }}</td>
                        <td class="py-3 px-4 text-slate-500">{{ $coach->specialization ?? '—' }}</td>
                        <td class="py-3 px-4">
                            @forelse ($coach->locations as $loc)
                                <span class="inline-flex items-center rounded-full bg-blue-50 text-blue-700 text-[11px] font-medium px-2 py-0.5 mr-1">
                                    {{ $loc->name }}
                                </span>
                            @empty
                                <span class="text-slate-400 text-xs">—</span>
                            @endforelse
                        </td>
                        <td class="py-3 px-4">
                            <x-badge :status="$coach->is_active ? 'active' : 'inactive'">
                                {{ $coach->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </td>
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-2 justify-end">
                                <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $coach->id }})">Edit</x-btn>
                                <x-btn variant="ghost" size="sm" wire:click="toggleActive({{ $coach->id }})">
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
        @if ($coaches->hasPages())
            <div class="px-4 py-3 border-t border-slate-100">
                {{ $coaches->links() }}
            </div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 sticky top-0 bg-white">
                    <h3 class="font-semibold text-slate-900">{{ $editingId ? 'Edit Coach' : 'Add Coach' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
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
                            <label class="block text-sm font-medium text-slate-700">Assigned Locations</label>
                            <div class="space-y-1.5 border border-slate-200 rounded-lg p-3">
                                @foreach ($locations as $loc)
                                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                                        <input type="checkbox" wire:model="selectedLocations" value="{{ $loc->id }}"
                                               class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                                        {{ $loc->name }}
                                        <span class="text-xs text-slate-400">{{ $loc->city }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500">
                        Active
                    </label>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1 justify-center" wire:click="$set('showModal', false)">Cancel</x-btn>
                    <x-btn class="flex-1 justify-center" wire:click="save" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
