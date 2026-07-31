<div>
    <div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.admin.report_cards.title')" :subtitle="__('messages.admin.report_cards.subtitle')">
        <x-slot name="action">
            <x-btn-add wire:click="openForm" :label="__('messages.admin.report_cards.new')" />
        </x-slot>
    </x-admin.page-header>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Status tabs --}}
    <div class="flex gap-2 flex-wrap mb-4">
        @foreach ([
            ''          => __('messages.common.all'),
            'draft'     => __('messages.status.draft'),
            'submitted' => __('messages.status.submitted'),
            'published' => __('messages.status.published'),
        ] as $val => $label)
            <button wire:click="$set('filterStatus', '{{ $val }}')"
                    @class([
                        'px-4 py-1.5 rounded-full text-sm font-semibold border transition-colors',
                        'bg-navy text-off border-navy'                 => $filterStatus === $val,
                        'bg-surface text-ink border-line hover:bg-off' => $filterStatus !== $val,
                    ])>{{ $label }}</button>
        @endforeach
    </div>

    {{-- Search --}}
    <div class="mb-4">
        <x-input wire:model.live.debounce.300ms="search" placeholder="{{ __('messages.admin.report_cards.search_ph') }}" />
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.report_cards.col_child') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.report_cards.col_coach') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.report_cards.col_period') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.report_cards.col_status') }}</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.admin.report_cards.col_published') }}</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($cards as $card)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 font-semibold text-ink">{{ $card->child->name }}</td>
                            <td class="py-3 px-4">
                                <p class="text-muted text-sm">{{ $card->coach->user->name }}</p>
                                @php $schedType = $card->enrollment?->schedule?->type; @endphp
                                @if ($schedType)
                                    <span @class([
                                        'text-3xs font-bold uppercase px-1.5 py-0.5 rounded-full',
                                        'bg-purple-50 text-purple-700' => $schedType === 'private',
                                        'bg-blue-50 text-blue-600'     => $schedType === 'regular',
                                    ])>{{ __('messages.admin.schedules.type_'.$schedType) }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $card->period_label }}</p>
                                <p class="text-xs text-faint">{{ $card->period_start->format('d M') }} – {{ $card->period_end->format('d M Y') }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$card->status">{{ __('messages.status.'.$card->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">
                                {{ $card->published_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($card->status === 'submitted')
                                    <x-btn variant="success" size="sm" wire:click="confirmPublish({{ $card->id }})" wire:loading.attr="disabled">
                                        {{ __('messages.admin.report_cards.publish') }}
                                    </x-btn>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-2">
                            <x-empty-state :title="__('messages.admin.report_cards.empty_title')" :description="__('messages.admin.report_cards.empty_desc')" />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cards->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $cards->links() }}</div>
        @endif
    </x-card>

    </div>{{-- /max-w-6xl --}}

    {{-- Create Modal --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeForm"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.report_cards.modal_new') }}</h3>
                <button wire:click="closeForm" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model.live="childId" label="{{ __('messages.admin.report_cards.label_child') }}" :error="$errors->first('childId')">
                    <option value="">{{ __('messages.admin.report_cards.select_child') }}</option>
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}">{{ $child->name }}</option>
                    @endforeach
                </x-select>

                @if ($childId)
                    <x-select wire:model.live="enrollmentId" label="{{ __('messages.admin.report_cards.label_enrollment') }}" :error="$errors->first('enrollmentId')">
                        <option value="">{{ __('messages.admin.report_cards.select_enrollment') }}</option>
                        @foreach ($enrollments as $enrollment)
                            <option value="{{ $enrollment->id }}">
                                {{ $enrollment->schedule?->program?->name ?? 'Program' }}
                                ({{ ucfirst($enrollment->schedule?->type ?? '') }}
                                — {{ ucfirst($enrollment->schedule?->day_of_week ?? '') }})
                            </option>
                        @endforeach
                    </x-select>
                @endif

                {{-- Coach field —  depends on enrollment type --}}
                @if ($coachLocked)
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.report_cards.label_coach') }}</label>
                        <div class="flex items-center gap-2 px-3.5 py-3 rounded-xl border border-line bg-off text-sm text-ink">
                            <svg class="w-4 h-4 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span class="flex-1">{{ collect($coaches)->firstWhere('id', $coachId)?->user?->name ?? __('messages.admin.report_cards.assigned_coach_ph') }}</span>
                            <span class="text-3xs font-bold uppercase tracking-wide text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">{{ __('messages.admin.report_cards.private_badge') }}</span>
                        </div>
                        <p class="text-xs text-faint">{{ __('messages.admin.report_cards.private_hint') }}</p>
                    </div>
                @elseif ($enrollmentId)
                    <x-select wire:model="coachId"
                              label="{{ __('messages.admin.report_cards.label_coach') }}{{ !empty($coachSuggestions) ? ' — '.__('messages.admin.report_cards.coach_sorted_hint') : '' }}"
                              :error="$errors->first('coachId')">
                        <option value="">{{ __('messages.admin.report_cards.select_coach') }}</option>
                        @if (!empty($coachSuggestions))
                            <optgroup label="{{ __('messages.admin.report_cards.optgroup_taught') }}">
                                @foreach ($coachSuggestions as $s)
                                    <option value="{{ $s['id'] }}">{{ $s['name'] }} ({{ $s['sessions'] }} session{{ $s['sessions'] > 1 ? 's' : '' }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="{{ __('messages.admin.report_cards.optgroup_others') }}">
                                @foreach ($coaches->whereNotIn('id', collect($coachSuggestions)->pluck('id')->toArray()) as $coach)
                                    <option value="{{ $coach->id }}">{{ $coach->user->name }}</option>
                                @endforeach
                            </optgroup>
                        @else
                            @foreach ($coaches as $coach)
                                <option value="{{ $coach->id }}">{{ $coach->user->name }}</option>
                            @endforeach
                        @endif
                    </x-select>
                @else
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.report_cards.label_coach') }}</label>
                        <div class="px-3.5 py-3 rounded-xl border border-line bg-off text-sm text-faint">{{ __('messages.admin.report_cards.select_enroll_first') }}</div>
                    </div>
                @endif

                <x-input wire:model="periodLabel" label="{{ __('messages.admin.report_cards.label_period_label') }}" placeholder="e.g. Jun–Jul 2026"
                         required :error="$errors->first('periodLabel')" />

                <div class="grid grid-cols-2 gap-4">
                    <x-input wire:model="periodStart" type="date" label="{{ __('messages.admin.report_cards.label_start_date') }}" required :error="$errors->first('periodStart')" />
                    <x-input wire:model="periodEnd" type="date" label="{{ __('messages.admin.report_cards.label_end_date') }}" required :error="$errors->first('periodEnd')" />
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.report_cards.label_notes') }}</label>
                    <textarea wire:model="overallNotes" rows="3" aria-label="{{ __('messages.admin.report_cards.label_notes') }}"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="{{ __('messages.admin.report_cards.notes_ph') }}"></textarea>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeForm">{{ __('messages.common.cancel') }}</x-btn>
                <x-btn class="flex-1" wire:click="create" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="create">{{ __('messages.common.create') }}</span>
                    <span wire:loading wire:target="create">{{ __('messages.admin.report_cards.creating') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif

    {{-- Publish Confirm Modal --}}
    @if ($showPublishModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="cancelPublish"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-sm">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.report_cards.confirm_publish') }}</h3>
                <button wire:click="cancelPublish" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6">
                <p class="text-sm text-muted">{{ __('messages.admin.report_cards.confirm_publish_desc') }}</p>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelPublish">{{ __('messages.common.cancel') }}</x-btn>
                <x-btn variant="success" class="flex-1" wire:click="publish" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="publish">{{ __('messages.admin.report_cards.yes_publish') }}</span>
                    <span wire:loading wire:target="publish">{{ __('messages.admin.report_cards.publishing') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
