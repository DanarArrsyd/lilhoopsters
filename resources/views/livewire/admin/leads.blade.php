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

    @if ($showModal)

        {{-- ═══ INLINE WIZARD ═══ --}}
        <div class="max-w-2xl mx-auto">

            {{-- Back link --}}
            <button wire:click="$set('showModal', false)"
                    class="inline-flex items-center gap-1.5 text-sm font-semibold text-muted hover:text-navy mb-5 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('messages.admin.leads.title') }}
            </button>

            {{-- Wizard card --}}
            <div class="bg-surface border border-line rounded-2xl overflow-hidden">

                {{-- Header + progress --}}
                <div class="px-6 pt-6 pb-5 border-b border-line">
                    <div class="flex items-start justify-between mb-5">
                        <div>
                            <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy">
                                {{ $editingId ? __('messages.admin.leads.modal_edit') : __('messages.admin.leads.modal_new') }}
                            </h2>
                            <p class="text-sm text-muted mt-0.5">
                                {{ __('messages.admin.leads.step_contact') }} &amp; {{ __('messages.admin.leads.step_details') }}
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
                        @foreach ([1 => __('messages.admin.leads.step_contact'), 2 => __('messages.admin.leads.step_details')] as $n => $label)
                            <div class="flex items-center gap-2">
                                <div @class([
                                    'w-6 h-6 rounded-full flex items-center justify-center text-2xs font-bold shrink-0 transition-colors',
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

                    {{-- ── Step 1: Contact + Source ── --}}
                    @if ($step === 1)
                        <div class="space-y-5" wire:key="lead-step-1">
                            <div class="grid grid-cols-2 gap-4">
                                <x-input wire:model="parent_name"
                                         label="{{ __('messages.admin.leads.label_parent_name') }}"
                                         placeholder="cth. Budi Santoso"
                                         required :error="$errors->first('parent_name')" />
                                <x-input wire:model="child_name"
                                         label="{{ __('messages.admin.leads.label_child_name') }}"
                                         placeholder="cth. Arka"
                                         :error="$errors->first('child_name')" />
                            </div>
                            <x-input wire:model="whatsapp"
                                     label="{{ __('messages.admin.leads.label_whatsapp') }}"
                                     placeholder="08xxxxxxxxxx"
                                     :error="$errors->first('whatsapp')" />

                            {{-- Source visual cards --}}
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy mb-3">
                                    {{ __('messages.admin.leads.label_source') }}
                                </label>
                                @php
                                    $sourceCards = [
                                        'walk_in'   => ['label' => $sourceLabel['walk_in'],   'path' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                                        'instagram' => ['label' => $sourceLabel['instagram'], 'path' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z'],
                                        'whatsapp'  => ['label' => $sourceLabel['whatsapp'],  'path' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
                                        'referral'  => ['label' => $sourceLabel['referral'],  'path' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                                        'web'       => ['label' => $sourceLabel['web'],       'path' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9'],
                                        'other'     => ['label' => $sourceLabel['other'],     'path' => 'M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z'],
                                    ];
                                @endphp
                                <div class="grid grid-cols-3 gap-3">
                                    @foreach ($sourceCards as $val => $card)
                                        <button type="button" wire:click="$set('source', '{{ $val }}')"
                                                @class([
                                                    'flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-150 text-center',
                                                    'border-navy bg-navy/5 shadow-sm' => $source === $val,
                                                    'border-line bg-surface hover:border-navy/30 hover:bg-off' => $source !== $val,
                                                ])>
                                            <svg class="w-6 h-6 {{ $source === $val ? 'text-navy' : 'text-faint' }}"
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="{{ $card['path'] }}"/>
                                            </svg>
                                            <span @class([
                                                'text-xs font-semibold leading-tight',
                                                'text-navy'  => $source === $val,
                                                'text-muted' => $source !== $val,
                                            ])>{{ $card['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ── Step 2: Interest & Status ── --}}
                    @if ($step === 2)
                        <div class="space-y-5" wire:key="lead-step-2">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                                        {{ __('messages.admin.leads.label_location') }}
                                    </label>
                                    <x-select wire:model="location_id">
                                        <option value="">—</option>
                                        @foreach ($locations as $loc)
                                            <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                                <div class="space-y-1.5">
                                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                                        {{ __('messages.admin.leads.label_program') }}
                                    </label>
                                    <x-select wire:model="program_id">
                                        <option value="">—</option>
                                        @foreach ($programs as $prog)
                                            <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                                        @endforeach
                                    </x-select>
                                </div>
                            </div>

                            {{-- Status visual selection --}}
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy mb-3">
                                    {{ __('messages.admin.leads.label_status') }}
                                </label>
                                @php
                                    $statusDots = [
                                        'new'             => 'bg-[#1D4ED8]',
                                        'contacted'       => 'bg-[#B45309]',
                                        'trial_scheduled' => 'bg-[#7C3AED]',
                                        'trial_done'      => 'bg-navy',
                                        'converted'       => 'bg-[#15803D]',
                                        'lost'            => 'bg-[#B91C1C]',
                                    ];
                                @endphp
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach ($statusMeta as $key => $meta)
                                        <button type="button" wire:click="$set('status', '{{ $key }}')"
                                                @class([
                                                    'flex items-center gap-2 px-3 py-2.5 rounded-xl border-2 transition-all text-left',
                                                    'border-navy bg-navy/5 shadow-sm' => $status === $key,
                                                    'border-line bg-surface hover:border-navy/30 hover:bg-off' => $status !== $key,
                                                ])>
                                            <span class="w-2 h-2 rounded-full shrink-0 {{ $statusDots[$key] ?? 'bg-faint' }}"></span>
                                            <span @class([
                                                'text-xs font-semibold',
                                                'text-navy'  => $status === $key,
                                                'text-muted' => $status !== $key,
                                            ])>{{ $meta['label'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Trial date --}}
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wide {{ in_array($status, ['trial_scheduled','trial_done']) ? 'text-navy' : 'text-muted' }}">
                                    {{ __('messages.admin.leads.label_trial_date') }}
                                    @unless (in_array($status, ['trial_scheduled','trial_done']))
                                        <span class="normal-case font-normal">(optional)</span>
                                    @endunless
                                </label>
                                <input type="date" wire:model="trial_date"
                                       class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-ink bg-off
                                              focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy
                                              {{ in_array($status, ['trial_scheduled','trial_done']) ? 'border-navy/40' : 'border-line' }}">
                                @error('trial_date') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                            </div>

                            {{-- Notes --}}
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">
                                    {{ __('messages.admin.leads.label_notes') }}
                                    <span class="normal-case font-normal text-faint">(optional)</span>
                                </label>
                                <textarea wire:model="notes" rows="3" aria-label="Lead notes"
                                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                                          placeholder="Additional notes..."></textarea>
                                @error('notes') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-line flex gap-3">
                    @if ($step === 1)
                        <button wire:key="lead-footer-cancel" wire:click="$set('showModal', false)"
                                class="px-5 py-2.5 text-sm font-semibold text-muted bg-off border border-line rounded-xl hover:bg-line/40 transition-colors">
                            {{ __('messages.common.cancel') }}
                        </button>
                        <button wire:key="lead-footer-next" wire:click="nextStep"
                                class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-off bg-navy rounded-xl hover:bg-navy/90 transition-colors">
                            {{ __('messages.common.next') }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    @else
                        <button wire:key="lead-footer-prev" wire:click="prevStep"
                                class="flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-muted bg-off border border-line rounded-xl hover:bg-line/40 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            {{ __('messages.common.back') }}
                        </button>
                        <button wire:key="lead-footer-save" wire:click="save" wire:loading.attr="disabled"
                                class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-off bg-navy rounded-xl hover:bg-navy/90 disabled:opacity-50 transition-colors">
                            <svg wire:loading.remove wire:target="save" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            <span wire:loading.remove wire:target="save">{{ $editingId ? __('messages.common.update') : __('messages.common.save') }}</span>
                            <span wire:loading wire:target="save">{{ __('messages.common.saving') }}</span>
                        </button>
                    @endif
                </div>

            </div>
        </div>

    @else

        {{-- ═══ LIST VIEW ═══ --}}
        <div class="max-w-6xl mx-auto">

        <x-admin.page-header :title="__('messages.admin.leads.title')" :subtitle="__('messages.admin.leads.subtitle')">
            <x-slot name="action">
                <x-btn-add wire:click="openCreate" :label="__('messages.admin.leads.new')" />
            </x-slot>
        </x-admin.page-header>

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
                                    <x-select variant="chip"
                                              wire:key="lead-status-{{ $lead->id }}"
                                              wire:change="setStatus({{ $lead->id }}, $event.target.value)"
                                              :trigger-class="$statusMeta[$lead->status]['cls']"
                                              :searchable="false"
                                              aria-label="{{ __('messages.admin.leads.label_status') }}">
                                        @foreach ($statusMeta as $key => $meta)
                                            <option value="{{ $key }}" @selected($lead->status === $key)>{{ $meta['label'] }}</option>
                                        @endforeach
                                    </x-select>
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

        </div>{{-- /max-w-6xl --}}

    @endif
</div>
