<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Report Cards</h2>
        <p class="text-sm text-muted">View your children's skill evaluations.</p>
    </div>

    {{-- Child filter --}}
    @if ($children->count() > 1)
        <x-card class="mb-4" padding="p-4">
            <div class="w-full sm:w-44">
                <x-select wire:model.live="filterChild">
                    <option value="">All Children</option>
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}">{{ $child->name }}</option>
                    @endforeach
                </x-select>
            </div>
        </x-card>
    @endif

    {{-- Cards list --}}
    @if ($cards->isEmpty())
        <x-empty-state title="No published report cards yet" description="Report cards will appear here once your coach submits them." />
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($cards as $card)
                <div class="bg-surface border border-line rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                     wire:click="openDetail({{ $card->id }})">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <p class="font-semibold text-ink">{{ $card->child->name }}</p>
                            <p class="text-sm text-muted">{{ $card->period_label }}</p>
                        </div>
                        <x-badge :status="$card->status">{{ ucfirst($card->status) }}</x-badge>
                    </div>
                    <div class="text-xs text-faint mb-3">
                        {{ $card->period_start->format('d M') }} – {{ $card->period_end->format('d M Y') }}
                        &nbsp;·&nbsp; Coach: {{ $card->coach->user->name }}
                    </div>
                    {{-- Mini score preview --}}
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ($card->scores as $score)
                            <div class="text-center">
                                <div class="text-xs text-muted capitalize mb-1">{{ $score->category }}</div>
                                <div class="flex justify-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <div class="w-2 h-2 rounded-full {{ $i <= $score->score ? 'bg-navy' : 'bg-line' }}"></div>
                                    @endfor
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $cards->links() }}</div>
    @endif

    {{-- Detail Modal --}}
    @if ($showDetail && $detailCard)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeDetail"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">{{ $detailCard->child->name }} — Report Card</h3>
                <button wire:click="closeDetail" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <div class="flex items-center justify-between text-sm text-muted">
                    <span>Period: <strong class="text-ink">{{ $detailCard->period_label }}</strong></span>
                    <span>Coach: <strong class="text-ink">{{ $detailCard->coach->user->name }}</strong></span>
                </div>
                <div class="text-xs text-faint">
                    {{ $detailCard->period_start->format('d M Y') }} – {{ $detailCard->period_end->format('d M Y') }}
                </div>

                <div class="border-t border-line pt-4 space-y-4">
                    @foreach ($detailCard->scores->sortBy('category') as $score)
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-semibold text-ink capitalize">{{ $score->category }}</span>
                                <span class="text-sm font-bold text-navy">{{ $score->score }}/5</span>
                            </div>
                            <div class="flex gap-1 mb-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <div class="flex-1 h-2 rounded-full {{ $i <= $score->score ? 'bg-navy' : 'bg-line' }}"></div>
                                @endfor
                            </div>
                            @if ($score->notes)
                                <p class="text-xs text-muted">{{ $score->notes }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($detailCard->overall_notes)
                    <div class="border-t border-line pt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-navy mb-1">Coach Notes</p>
                        <p class="text-sm text-ink">{{ $detailCard->overall_notes }}</p>
                    </div>
                @endif
            </div>
            <div class="px-6 pb-6">
                <x-btn variant="secondary" wire:click="closeDetail">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Close
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
