<div>
    @if ($showModal)

        {{-- ═══ INLINE WIZARD ═══ --}}
        <div class="max-w-2xl mx-auto">

            {{-- Back link --}}
            <button wire:click="$set('showModal', false)"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.admin.programs.title') }}
            </button>

            {{-- Wizard card --}}
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">

                {{-- Header + progress --}}
                <div class="px-6 pt-6 pb-5 border-b border-line">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy">
                                {{ $editingId ? __('messages.admin.programs.modal_edit') : __('messages.admin.programs.modal_new') }}
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                {{ __('messages.admin.programs.step_info') }} &amp; {{ __('messages.admin.programs.step_details') }}
                            </p>
                        </div>
                        <span class="text-sm font-bold text-faint">{{ $step }}/2</span>
                    </div>

                    {{-- Progress bar --}}
                    <div class="h-1.5 bg-line rounded-full overflow-hidden mb-4">
                        <div class="h-full bg-navy rounded-full transition-all duration-300"
                             style="width: {{ $step / 2 * 100 }}%"></div>
                    </div>

                    {{-- Step dots --}}
                    <div class="flex items-center">
                        @foreach ([1 => __('messages.admin.programs.step_info'), 2 => __('messages.admin.programs.step_details')] as $n => $label)
                            <div class="flex items-center gap-2">
                                <div @class([
                                    'w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-colors',
                                    'bg-[#15803D] text-white' => $step > $n,
                                    'bg-navy text-off'        => $step === $n,
                                    'bg-line text-faint'      => $step < $n,
                                ])>
                                    @if ($step > $n)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @else
                                        {{ $n }}
                                    @endif
                                </div>
                                <span @class(['text-sm font-semibold transition-colors', 'text-navy' => $step === $n, 'text-faint' => $step !== $n])>
                                    {{ $label }}
                                </span>
                            </div>
                            @if ($n < 2)
                                <div class="flex-1 h-px bg-line mx-3"></div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Form content --}}
                <div class="px-6 py-6">

                    {{-- ── Step 1: Name + Age Range ── --}}
                    @if ($step === 1)
                        <div class="space-y-5" wire:key="program-step-1">
                            <x-input wire:model="name"
                                     label="{{ __('messages.admin.programs.label_name') }}"
                                     placeholder="e.g. Junior, Rookie, MVP"
                                     required :error="$errors->first('name')" />

                            <div class="grid grid-cols-2 gap-4">
                                <x-input wire:model="minAgeYears" type="number"
                                         label="{{ __('messages.admin.programs.label_min_age') }}"
                                         placeholder="1" required :error="$errors->first('minAgeYears')" />
                                <x-input wire:model="maxAgeYears" type="number"
                                         label="{{ __('messages.admin.programs.label_max_age') }}"
                                         placeholder="7" required :error="$errors->first('maxAgeYears')" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 mt-6 pt-5 border-t border-line">
                            <button wire:click="$set('showModal', false)"
                                    class="text-sm font-semibold text-muted hover:text-navy transition-colors px-4 py-2">
                                {{ __('messages.common.cancel') }}
                            </button>
                            <button wire:click="nextStep" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 bg-navy text-off text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="nextStep">{{ __('messages.common.next') }}</span>
                                <span wire:loading wire:target="nextStep">...</span>
                                <svg wire:loading.remove wire:target="nextStep" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>

                    {{-- ── Step 2: Description + Active ── --}}
                    @elseif ($step === 2)
                        <div class="space-y-5" wire:key="program-step-2">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.programs.label_desc') }}</label>
                                <textarea wire:model="description" rows="4"
                                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                          placeholder="Optional program description..."></textarea>
                                @error('description') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                            </div>

                            <div class="flex items-center justify-between px-5 py-4 bg-off rounded-xl border border-line">
                                <p class="text-sm font-semibold text-ink">{{ __('messages.admin.programs.label_active') }}</p>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                    <div class="w-10 h-6 bg-line peer-focus:outline-none rounded-full peer
                                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:bg-navy"></div>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-3 mt-6 pt-5 border-t border-line">
                            <button wire:click="back"
                                    class="inline-flex items-center gap-2 text-sm font-semibold text-muted hover:text-navy transition-colors px-4 py-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                {{ __('messages.common.back') }}
                            </button>
                            <button wire:click="save" wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 bg-navy text-off text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-navy/90 active:scale-[0.97] transition-all disabled:opacity-50">
                                <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <span wire:loading.remove wire:target="save">
                                    {{ $editingId ? __('messages.common.update') : __('messages.common.save') }}
                                </span>
                                <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                            </button>
                        </div>
                    @endif

                </div>
            </div>
        </div>

    @else

        {{-- ═══ LIST VIEW ═══ --}}
        <div class="max-w-6xl mx-auto">

        <x-admin.page-header :title="__('messages.admin.programs.title')" :subtitle="__('messages.admin.programs.subtitle')">
            <x-slot name="action">
                <x-btn-add wire:click="openCreate" :label="__('messages.admin.programs.new')" />
            </x-slot>
        </x-admin.page-header>

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        <x-card class="mb-4" padding="p-4">
            <x-input wire:model.live.debounce.300ms="search" placeholder="Search programs..." />
        </x-card>

        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[640px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.programs.col_program') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.programs.col_age_range') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.programs.col_desc') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.programs.col_status') }}</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($programs as $program)
                            <tr class="hover:bg-off transition-colors">
                                <td class="py-3 px-4 font-semibold text-ink">{{ $program->name }}</td>
                                <td class="py-3 px-4">
                                    <span class="font-mono bg-navy/8 text-navy rounded px-1.5 py-0.5 text-xs">
                                        {{ round($program->min_age_months / 12) }}–{{ round($program->max_age_months / 12) }} yrs
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-muted max-w-xs truncate">{{ $program->description ?? '—' }}</td>
                                <td class="py-3 px-4">
                                    <x-badge :status="$program->is_active ? 'active' : 'inactive'">
                                        {{ $program->is_active ? __('messages.status.active') : __('messages.status.inactive') }}
                                    </x-badge>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2 justify-end">
                                        <x-btn variant="edit" size="sm" wire:click="openEdit({{ $program->id }})" wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            {{ __('messages.common.edit') }}
                                        </x-btn>
                                        <x-btn variant="{{ $program->is_active ? 'warning' : 'success' }}" size="sm"
                                               wire:click="toggleActive({{ $program->id }})" wire:loading.attr="disabled">
                                            @if ($program->is_active)
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                {{ __('messages.common.deactivate') }}
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                                {{ __('messages.common.activate') }}
                                            @endif
                                        </x-btn>
                                        <x-btn variant="danger" size="sm"
                                               wire:click="confirmDelete({{ $program->id }})"
                                               wire:confirm="Delete this program?"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            {{ __('messages.common.delete') }}
                                        </x-btn>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-2">
                                    <x-empty-state :title="__('messages.admin.programs.empty_title')" :description="__('messages.admin.programs.empty_desc')" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($programs->hasPages())
                <div class="px-4 py-3 border-t border-line">{{ $programs->links() }}</div>
            @endif
        </x-card>

        </div>{{-- /max-w-6xl --}}

    @endif
</div>
