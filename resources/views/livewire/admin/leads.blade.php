<div>
    @php
        $statusMeta = [
            'new'             => ['label' => __('messages.admin.leads.status_new'),         'cls' => 'bg-[#1D4ED8]/10 text-[#1D4ED8]'],
            'contacted'       => ['label' => __('messages.admin.leads.status_contacted'),    'cls' => 'bg-[#B45309]/10 text-[#B45309]'],
            'trial_scheduled' => ['label' => __('messages.admin.leads.status_trial_sched'), 'cls' => 'bg-[#7C3AED]/10 text-[#7C3AED]'],
            'trial_done'      => ['label' => __('messages.admin.leads.status_trial_done'),  'cls' => 'bg-navy/10 text-navy'],
            'converted'       => ['label' => __('messages.admin.leads.status_converted'),   'cls' => 'bg-[#15803D]/10 text-[#15803D]'],
            'lost'            => ['label' => __('messages.admin.leads.status_lost'),        'cls' => 'bg-[#B91C1C]/10 text-[#B91C1C]'],
        ];
        $sourceLabel = [
            'walk_in'   => __('messages.admin.leads.source_walk_in'),
            'instagram' => __('messages.admin.leads.source_instagram'),
            'whatsapp'  => __('messages.admin.leads.source_whatsapp'),
            'referral'  => __('messages.admin.leads.source_referral'),
            'web'       => __('messages.admin.leads.source_web'),
            'other'     => __('messages.admin.leads.source_other'),
        ];
    @endphp

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.leads.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.admin.leads.subtitle') }}</p>
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

    {{-- Status filter tabs --}}
    <div class="flex flex-wrap items-center gap-1.5 mb-4">
        <button wire:click="$set('statusFilter', '')"
                class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors
                       {{ $statusFilter === '' ? 'bg-navy text-off border-navy' : 'bg-surface text-muted border-line hover:text-ink' }}">
            {{ __('messages.common.all') }} <span class="opacity-70">{{ $total }}</span>
        </button>
        @foreach ($statusMeta as $key => $meta)
            <button wire:click="$set('statusFilter', '{{ $key }}')"
                    class="px-3 py-1.5 text-xs font-semibold rounded-lg border transition-colors
                           {{ $statusFilter === $key ? 'bg-navy text-off border-navy' : 'bg-surface text-muted border-line hover:text-ink' }}">
                {{ $meta['label'] }} <span class="opacity-70">{{ $counts[$key] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    <x-card class="mb-4" padding="p-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search parent / child / WhatsApp..." />
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[760px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leads.col_prospect') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leads.col_source') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leads.col_interest') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leads.col_trial') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.leads.col_status') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-off transition-colors" wire:key="lead-{{ $lead->id }}">
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $lead->parent_name }}</p>
                                <p class="text-xs text-muted">
                                    {{ $lead->child_name ?: '—' }}
                                    @if ($lead->whatsapp) · {{ $lead->whatsapp }} @endif
                                </p>
                            </td>
                            <td class="py-3 px-4 text-muted">{{ $sourceLabel[$lead->source] ?? $lead->source }}</td>
                            <td class="py-3 px-4 text-muted">
                                {{ $lead->program?->name ?? '—' }}
                                <span class="block text-xs text-faint">{{ $lead->location?->name }}</span>
                            </td>
                            <td class="py-3 px-4 text-muted">
                                {{ $lead->trial_date ? $lead->trial_date->format('d M Y') : '—' }}
                            </td>
                            <td class="py-3 px-4">
                                <select wire:change="setStatus({{ $lead->id }}, $event.target.value)"
                                        class="text-xs font-semibold rounded-lg border-0 py-1 pl-2 pr-7 cursor-pointer focus:ring-2 focus:ring-navy/20 {{ $statusMeta[$lead->status]['cls'] }}">
                                    @foreach ($statusMeta as $key => $meta)
                                        <option value="{{ $key }}" @selected($lead->status === $key)>{{ $meta['label'] }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="py-3 px-4">
                                <div class="flex items-center gap-2 justify-end">
                                    <x-btn variant="edit" size="sm" wire:click="openEdit({{ $lead->id }})" wire:loading.attr="disabled">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        {{ __('messages.common.edit') }}
                                    </x-btn>
                                    <x-btn variant="danger" size="sm"
                                           wire:click="confirmDelete({{ $lead->id }})"
                                           wire:confirm="Delete this lead?"
                                           wire:loading.attr="disabled"
                                           wire:target="confirmDelete({{ $lead->id }})">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        {{ __('messages.common.delete') }}
                                    </x-btn>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state :title="__('messages.admin.leads.empty_title')" :description="__('messages.admin.leads.empty_desc')" />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($leads->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $leads->links() }}</div>
        @endif
    </x-card>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="$set('showModal', false)"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line sticky top-0 bg-surface">
                    <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $editingId ? __('messages.admin.leads.modal_edit') : __('messages.admin.leads.modal_new') }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-muted hover:text-navy p-1 leading-none">✕</button>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input wire:model="parent_name" label="{{ __('messages.admin.leads.label_parent_name') }}" placeholder="cth. Budi Santoso"
                                 required :error="$errors->first('parent_name')" />
                        <x-input wire:model="child_name" label="{{ __('messages.admin.leads.label_child_name') }}" placeholder="cth. Arka"
                                 :error="$errors->first('child_name')" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input wire:model="whatsapp" label="{{ __('messages.admin.leads.label_whatsapp') }}" placeholder="08xxxxxxxxxx"
                                 :error="$errors->first('whatsapp')" />
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.leads.label_source') }}</label>
                            <x-select wire:model="source">
                                @foreach ($sourceLabel as $val => $lbl)
                                    <option value="{{ $val }}">{{ $lbl }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.leads.label_location') }}</label>
                            <x-select wire:model="location_id">
                                <option value="">—</option>
                                @foreach ($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.leads.label_program') }}</label>
                            <x-select wire:model="program_id">
                                <option value="">—</option>
                                @foreach ($programs as $prog)
                                    <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                                @endforeach
                            </x-select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.leads.label_status') }}</label>
                            <x-select wire:model="status">
                                @foreach ($statusMeta as $key => $meta)
                                    <option value="{{ $key }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </x-select>
                        </div>
                        <x-input wire:model="trial_date" type="date" label="{{ __('messages.admin.leads.label_trial_date') }}"
                                 :error="$errors->first('trial_date')" />
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.leads.label_notes') }}</label>
                        <textarea wire:model="notes" rows="3" aria-label="Lead notes"
                                  class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                  placeholder="Additional notes..."></textarea>
                        @error('notes') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>
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
