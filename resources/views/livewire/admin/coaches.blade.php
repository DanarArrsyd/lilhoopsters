<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.coaches.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.admin.coaches.subtitle') }}</p>
    </div>
    <button wire:click="openCreate"
            class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

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
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_coach') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_phone') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_specialization') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_locations') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.coaches.col_status') }}</th>
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
                                    {{ $coach->is_active ? __('messages.status.active') : __('messages.status.inactive') }}
                                </x-badge>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="edit" size="sm"
                                        wire:click="openEdit({{ $coach->id }})"
                                        wire:loading.attr="disabled">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        {{ __('messages.common.edit') }}
                                    </x-btn>
                                    <x-btn variant="{{ $coach->is_active ? 'warning' : 'success' }}" size="sm"
                                        wire:click="toggleActive({{ $coach->id }})"
                                        wire:loading.attr="disabled">
                                        @if ($coach->is_active)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            {{ __('messages.common.deactivate') }}
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                            {{ __('messages.common.activate') }}
                                        @endif
                                    </x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state :title="__('messages.admin.coaches.empty_title')" :description="__('messages.admin.coaches.empty_desc')" />
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
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? __('messages.admin.coaches.modal_edit') : __('messages.admin.coaches.modal_new') }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <x-input wire:model="coach_name" label="{{ __('messages.admin.coaches.label_name') }}" placeholder="Coach name"
                             required :error="$errors->first('coach_name')" />
                    <x-input wire:model="coach_email" type="email" label="{{ __('messages.admin.coaches.label_email') }}" placeholder="coach@example.com"
                             required :error="$errors->first('coach_email')" />
                    <x-input wire:model="coach_password" type="password"
                             label="{{ $editingId ? __('messages.admin.coaches.label_new_password') : __('messages.admin.coaches.label_password') }}"
                             placeholder="{{ $editingId ? __('messages.admin.coaches.label_keep_pw') : __('messages.admin.coaches.label_password_ph') }}"
                             :required="!$editingId" :error="$errors->first('coach_password')" />
                    <x-input wire:model="phone" label="{{ __('messages.admin.coaches.label_phone') }}" placeholder="081234567890"
                             required :error="$errors->first('phone')" />
                    <x-input wire:model="specialization" label="{{ __('messages.admin.coaches.label_spec') }}"
                             placeholder="e.g. Dribbling, Defense"
                             :error="$errors->first('specialization')" />

                    <label class="flex items-center gap-2 text-sm text-ink cursor-pointer">
                        <input type="checkbox" wire:model="is_active" class="rounded accent-navy"> {{ __('messages.admin.coaches.label_active') }}
                    </label>
                </div>
                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1" wire:click="$set('showModal', false)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        {{ __('messages.common.cancel') }}
                    </x-btn>
                    <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span wire:loading.remove wire:target="save">{{ $editingId ? __('messages.common.update') : __('messages.common.save') }}</span>
                        <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
