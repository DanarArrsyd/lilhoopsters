<div class="max-w-6xl mx-auto">
    <x-admin.page-header :title="__('messages.coach.report_cards.title')" :subtitle="__('messages.coach.report_cards.subtitle')" />

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filter --}}
    <x-card class="mb-4" padding="p-4">
        <div class="w-full sm:w-44">
            <x-select wire:model.live="filterStatus">
                <option value="">{{ __('messages.coach.report_cards.all_statuses') }}</option>
                <option value="draft">{{ __('messages.status.draft') }}</option>
                <option value="submitted">{{ __('messages.status.submitted') }}</option>
                <option value="published">{{ __('messages.status.published') }}</option>
            </x-select>
        </div>
    </x-card>

    {{-- Table --}}
    <x-card padding="p-0">
        @if ($cards->isEmpty())
            <x-empty-state :title="__('messages.coach.report_cards.empty_title')" :description="__('messages.coach.report_cards.empty_desc')" />
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.coach.report_cards.col_student') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.coach.report_cards.col_period') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.coach.report_cards.col_scores') }}</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">{{ __('messages.coach.report_cards.col_status') }}</th>
                            <th class="py-3 px-4"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($cards as $card)
                            <tr class="hover:bg-off transition-colors">
                                <td class="py-3 px-4 font-semibold text-ink">{{ $card->child->name }}</td>
                                <td class="py-3 px-4">
                                    <p class="font-semibold text-ink">{{ $card->period_label }}</p>
                                    <p class="text-xs text-faint">{{ $card->period_start->format('d M') }} – {{ $card->period_end->format('d M Y') }}</p>
                                </td>
                                <td class="py-3 px-4 text-muted">{{ __('messages.coach.report_cards.categories', ['n' => $card->scores->count()]) }}</td>
                                <td class="py-3 px-4">
                                    <x-badge :status="$card->status">{{ __('messages.status.'.$card->status) }}</x-badge>
                                </td>
                                <td class="py-3 px-4">
                                    @if (in_array($card->status, ['draft', 'submitted']))
                                        <x-btn variant="edit" size="sm"
                                               wire:click="openScoreModal({{ $card->id }})"
                                               wire:loading.attr="disabled">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            {{ $card->status === 'draft' ? __('messages.coach.report_cards.input_scores') : __('messages.coach.report_cards.edit_scores') }}
                                        </x-btn>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-line">
                {{ $cards->links() }}
            </div>
        @endif
    </x-card>

    {{-- Score Input Modal --}}
    @if ($showScoreModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeScoreModal"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ __('messages.coach.report_cards.input_scores') }}</h3>
                <button wire:click="closeScoreModal" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-3">
                @foreach (['dribbling', 'passing', 'shooting', 'defense', 'attitude', 'discipline'] as $cat)
                    <div class="border border-line rounded-xl p-4 space-y-3">
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-semibold text-ink capitalize w-24">{{ $cat }}</span>
                            <div class="flex gap-2">
                                @foreach ([1,2,3,4,5] as $val)
                                    <button type="button"
                                        wire:click="$set('scores.{{ $cat }}.score', {{ $val }})"
                                        class="w-9 h-9 rounded-full text-sm font-bold border-2 transition-colors
                                            {{ ($scores[$cat]['score'] ?? '') == $val
                                                ? 'bg-navy text-off border-navy'
                                                : 'text-ink border-line hover:border-navy hover:bg-off' }}">
                                        {{ $val }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <x-input wire:model="scores.{{ $cat }}.notes" placeholder="Notes (optional)" />
                        @error("scores.{$cat}.score") <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeScoreModal">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    {{ __('messages.common.cancel') }}
                </x-btn>
                <x-btn class="flex-1" wire:click="saveScores" wire:loading.attr="disabled">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <span wire:loading.remove wire:target="saveScores">{{ __('messages.coach.report_cards.save_scores') }}</span>
                    <span wire:loading wire:target="saveScores">{{ __('messages.common.saving') }}</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
