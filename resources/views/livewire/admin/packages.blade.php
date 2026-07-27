<div>
    @php
        $typeMeta = [
            'registration' => ['label' => __('messages.admin.packages.type_reg'),     'class' => 'bg-[#1D4ED8]/10 text-[#1D4ED8]', 'dot' => 'bg-[#1D4ED8]'],
            'regular'      => ['label' => __('messages.admin.packages.type_regular'), 'class' => 'bg-navy/10 text-navy',             'dot' => 'bg-navy'],
            'drop_in'      => ['label' => __('messages.admin.packages.type_drop_in'), 'class' => 'bg-[#B45309]/10 text-[#B45309]',  'dot' => 'bg-[#B45309]'],
            'private'      => ['label' => __('messages.admin.packages.type_private'), 'class' => 'bg-[#7C3AED]/10 text-[#7C3AED]',  'dot' => 'bg-[#7C3AED]'],
        ];
    @endphp

    @if ($showModal)

        {{-- ═══ INLINE WIZARD ═══ --}}
        <div class="max-w-2xl mx-auto">

            {{-- Back link --}}
            <button wire:click="$set('showModal', false)"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.admin.packages.title') }}
            </button>

            {{-- Wizard card --}}
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">

                {{-- Header + progress --}}
                <div class="px-6 pt-6 pb-5 border-b border-line">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy">
                                {{ $editingId ? __('messages.admin.packages.modal_edit') : __('messages.admin.packages.modal_new') }}
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                {{ __('messages.admin.packages.step_info') }} &amp; {{ __('messages.admin.packages.step_details') }}
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
                        @foreach ([1 => __('messages.admin.packages.step_info'), 2 => __('messages.admin.packages.step_details')] as $n => $label)
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

                    {{-- ── Step 1: Location + Name + Type + Price ── --}}
                    @if ($step === 1)
                        <div class="space-y-5" wire:key="package-step-1">
                            <x-select wire:model="location_id"
                                      label="{{ __('messages.admin.packages.label_location') }}"
                                      :error="$errors->first('location_id')">
                                <option value="">{{ __('messages.admin.packages.select_location') }}</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </x-select>

                            <x-input wire:model="name"
                                     label="{{ __('messages.admin.packages.label_name') }}"
                                     placeholder="e.g. Regular Apr–Mei"
                                     required :error="$errors->first('name')" />

                            <x-select wire:model.live="type"
                                      label="{{ __('messages.admin.packages.label_type') }}"
                                      :error="$errors->first('type')">
                                @foreach ($typeMeta as $val => $meta)
                                    <option value="{{ $val }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </x-select>

                            <x-input wire:model="price" type="number"
                                     label="{{ __('messages.admin.packages.label_price') }}"
                                     placeholder="350000"
                                     required :error="$errors->first('price')" />
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

                    {{-- ── Step 2: Details (conditional by type) ── --}}
                    @elseif ($step === 2)
                        <div class="space-y-5" wire:key="package-step-2">

                            {{-- Conditional fields by type --}}
                            @if ($type === 'regular')
                                <x-input wire:model="validity_days" type="number"
                                         label="{{ __('messages.admin.packages.label_validity') }}"
                                         placeholder="e.g. 30"
                                         required :error="$errors->first('validity_days')"
                                         helper="How many days the package is valid from the parent's chosen start date." />
                                <x-input wire:model="session_count" type="number"
                                         label="{{ __('messages.admin.packages.label_sessions') }}"
                                         placeholder="e.g. 8"
                                         :error="$errors->first('session_count')" />
                            @elseif ($type === 'drop_in')
                                <x-input wire:model="validity_days" type="number"
                                         label="{{ __('messages.admin.packages.label_validity') }}"
                                         placeholder="30"
                                         :error="$errors->first('validity_days')" />
                            @elseif ($type === 'private')
                                <x-input wire:model="session_count" type="number"
                                         label="{{ __('messages.admin.packages.label_sessions') }}"
                                         placeholder="Leave blank for unlimited"
                                         :error="$errors->first('session_count')" />
                            @endif

                            {{-- Description --}}
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.packages.label_desc') }}</label>
                                <textarea wire:model="description" rows="3"
                                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                          placeholder="{{ __('messages.admin.packages.desc_ph') }}"></textarea>
                            </div>

                            {{-- Toggles --}}
                            <div class="flex items-center justify-between px-5 py-4 bg-off rounded-xl border border-line">
                                <p class="text-sm font-semibold text-ink">{{ __('messages.admin.packages.label_active') }}</p>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                    <div class="w-10 h-6 bg-line peer-focus:outline-none rounded-full peer
                                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:bg-navy"></div>
                                </label>
                            </div>

                            <div class="flex items-center justify-between px-5 py-4 bg-off rounded-xl border border-line">
                                <p class="text-sm font-semibold text-ink">{{ __('messages.admin.packages.label_popular') }}</p>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="is_popular" class="sr-only peer">
                                    <div class="w-10 h-6 bg-line peer-focus:outline-none rounded-full peer
                                                peer-checked:after:translate-x-full peer-checked:after:border-white
                                                after:content-[''] after:absolute after:top-[2px] after:left-[2px]
                                                after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all
                                                peer-checked:bg-[#B45309]"></div>
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

        <x-admin.page-header :title="__('messages.admin.packages.title')" :subtitle="__('messages.admin.packages.subtitle')">
            <x-slot name="action">
                <x-btn-add wire:click="openCreate" :label="__('messages.admin.packages.new')" />
            </x-slot>
        </x-admin.page-header>

        @if (session('success'))
            <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
        @endif

        {{-- Filters --}}
        <x-card class="mb-4" padding="p-4">
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <div class="flex-1">
                    <x-input wire:model.live.debounce.300ms="search" placeholder="Search packages..." />
                </div>
                <div class="w-full sm:w-56">
                    <x-select wire:model.live="filterLocation">
                        <option value="">{{ __('messages.admin.packages.all_locations') }}</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                        @endforeach
                    </x-select>
                </div>
            </div>
            @if ($totalCount > 0)
                <p class="text-xs text-muted mt-3">
                    <span class="font-semibold text-ink">{{ $totalCount }}</span>
                    package{{ $totalCount === 1 ? '' : 's' }}
                    across <span class="font-semibold text-ink">{{ $locationCount }}</span>
                    location{{ $locationCount === 1 ? '' : 's' }}.
                </p>
            @endif
        </x-card>

        {{-- Grouped by location --}}
        @forelse ($groups as $group)
            @php
                $location    = $group->first()->location;
                $activeCount = $group->where('is_active', true)->count();
            @endphp

            <x-card padding="p-0" class="mb-4 overflow-hidden"
                    x-data="{ expanded: {{ ($search || $filterLocation) ? 'true' : 'false' }} }">
                <button type="button" x-on:click="expanded = !expanded"
                        class="w-full flex items-center gap-3 px-4 sm:px-5 py-3.5 text-left transition-colors hover:bg-navy/[0.05]"
                        :class="expanded ? 'bg-navy/[0.04]' : 'bg-navy/[0.02]'">
                    <div class="w-9 h-9 rounded-xl bg-navy/8 text-navy flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-extrabold text-navy text-sm sm:text-base truncate leading-tight">{{ $location?->name ?? 'Unassigned' }}</p>
                        @if ($location?->address)
                            <p class="text-[11px] text-muted truncate">{{ $location->address }}</p>
                        @endif
                    </div>
                    <span class="shrink-0 text-[11px] font-semibold text-muted tabular-nums">
                        {{ $group->count() }} {{ $group->count() === 1 ? 'package' : 'packages' }}
                        <span class="text-faint hidden sm:inline">· {{ $activeCount }} active</span>
                    </span>
                    <svg class="w-4 h-4 text-muted shrink-0 transition-transform duration-200"
                         :class="{ 'rotate-180': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <div x-show="expanded" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="overflow-x-auto border-t border-line">
                    <table class="w-full text-sm min-w-[680px]">
                        <tbody class="divide-y divide-line">
                            @foreach ($group as $package)
                                @php $meta = $typeMeta[$package->type] ?? ['label' => str_replace('_', ' ', $package->type), 'class' => 'bg-navy/5 text-muted', 'dot' => 'bg-muted']; @endphp
                                <tr class="hover:bg-off transition-colors {{ $package->is_active ? '' : 'opacity-60' }}">
                                    <td class="py-3 pl-4 sm:pl-5 pr-4">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $meta['dot'] }}"></span>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-semibold text-ink truncate">{{ $package->name }}</span>
                                                    @if ($package->is_popular)
                                                        <span class="inline-flex items-center gap-0.5 text-[10px] font-bold text-[#B45309] uppercase tracking-wide shrink-0">
                                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                            {{ __('messages.admin.packages.popular_badge') }}
                                                        </span>
                                                    @endif
                                                </div>
                                                @php
                                                    $bits = [];
                                                    if ($package->session_count) $bits[] = $package->session_count . ' session' . ($package->session_count === 1 ? '' : 's');
                                                    if ($package->validity_days) $bits[] = $package->validity_days . '-day validity';
                                                @endphp
                                                @if ($bits)
                                                    <p class="text-[11px] text-muted truncate">{{ implode(' · ', $bits) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wide {{ $meta['class'] }}">
                                            {{ $meta['label'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        <span class="font-bold text-ink tabular-nums whitespace-nowrap">{{ $package->formattedPrice() }}</span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <x-badge :status="$package->is_active ? 'active' : 'inactive'">
                                            {{ $package->is_active ? __('messages.status.active') : __('messages.status.inactive') }}
                                        </x-badge>
                                    </td>
                                    <td class="py-3 pr-4 sm:pr-5 pl-4">
                                        <div class="flex items-center gap-2 justify-end">
                                            <x-btn variant="edit" size="sm" wire:click="openEdit({{ $package->id }})" wire:loading.attr="disabled">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                {{ __('messages.common.edit') }}
                                            </x-btn>
                                            <x-btn variant="{{ $package->is_active ? 'warning' : 'success' }}" size="sm"
                                                   wire:click="toggleActive({{ $package->id }})" wire:loading.attr="disabled">
                                                @if ($package->is_active)
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                                    {{ __('messages.common.deactivate') }}
                                                @else
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 5.636a9 9 0 1012.728 0M12 3v9"/></svg>
                                                    {{ __('messages.common.activate') }}
                                                @endif
                                            </x-btn>
                                            <x-btn variant="danger" size="sm"
                                                   wire:click="confirmDelete({{ $package->id }})"
                                                   wire:confirm="Delete this package?" wire:loading.attr="disabled">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                {{ __('messages.common.delete') }}
                                            </x-btn>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        @empty
            <x-card padding="p-0">
                <x-empty-state
                    :title="$search || $filterLocation ? __('messages.admin.packages.empty_no_match') : __('messages.admin.packages.empty_title')"
                    :description="$search || $filterLocation ? __('messages.admin.packages.empty_nm_desc') : __('messages.admin.packages.empty_desc')" />
            </x-card>
        @endforelse

        </div>{{-- /max-w-6xl --}}

    @endif
</div>
