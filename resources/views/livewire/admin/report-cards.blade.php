<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Report Cards</h2>
        <p class="text-sm text-muted">Manage and publish student report cards.</p>
    </div>
    <button wire:click="openForm"
            class="fixed bottom-6 right-5 z-30 w-14 h-14 bg-navy text-off rounded-full shadow-lg flex items-center justify-center hover:bg-navy/90 active:scale-95 transition-all">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
        </svg>
    </button>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Status tabs --}}
    <div class="flex gap-2 flex-wrap mb-4">
        @foreach ([
            ''          => 'All',
            'draft'     => 'Draft',
            'submitted' => 'Submitted',
            'published' => 'Published',
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
        <x-input wire:model.live.debounce.300ms="search" placeholder="Search by child name..." />
    </div>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[640px]">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Child</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Coach</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Period</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Published</th>
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
                                        'text-[10px] font-bold uppercase px-1.5 py-0.5 rounded-full',
                                        'bg-purple-50 text-purple-700' => $schedType === 'private',
                                        'bg-blue-50 text-blue-600'     => $schedType === 'regular',
                                    ])>{{ ucfirst($schedType) }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-4">
                                <p class="font-semibold text-ink">{{ $card->period_label }}</p>
                                <p class="text-xs text-faint">{{ $card->period_start->format('d M') }} – {{ $card->period_end->format('d M Y') }}</p>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$card->status">{{ ucfirst($card->status) }}</x-badge>
                            </td>
                            <td class="py-3 px-4 text-xs text-faint">
                                {{ $card->published_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if ($card->status === 'submitted')
                                    <x-btn variant="success" size="sm" wire:click="confirmPublish({{ $card->id }})" wire:loading.attr="disabled">
                                        Publish
                                    </x-btn>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-2">
                            <x-empty-state title="No report cards found" description="Create the first report card for a student." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cards->hasPages())
            <div class="px-4 py-3 border-t border-line">{{ $cards->links() }}</div>
        @endif
    </x-card>

    {{-- Create Modal --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="closeForm"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">New Report Card</h3>
                <button wire:click="closeForm" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model.live="childId" label="Child" :error="$errors->first('childId')">
                    <option value="">Select child...</option>
                    @foreach ($children as $child)
                        <option value="{{ $child->id }}">{{ $child->name }}</option>
                    @endforeach
                </x-select>

                @if ($childId)
                    <x-select wire:model.live="enrollmentId" label="Enrollment" :error="$errors->first('enrollmentId')">
                        <option value="">Select enrollment...</option>
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
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Coach</label>
                        <div class="flex items-center gap-2 px-3.5 py-3 rounded-xl border border-line bg-off text-sm text-ink">
                            <svg class="w-4 h-4 text-purple-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            <span class="flex-1">{{ collect($coaches)->firstWhere('id', $coachId)?->user?->name ?? 'Assigned coach' }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wide text-purple-600 bg-purple-50 px-2 py-0.5 rounded-full">Private — Auto-assigned</span>
                        </div>
                        <p class="text-xs text-faint">Coach is fixed for private schedules.</p>
                    </div>
                @elseif ($enrollmentId)
                    <x-select wire:model="coachId"
                              label="Coach{{ !empty($coachSuggestions) ? ' — sorted by sessions taught' : '' }}"
                              :error="$errors->first('coachId')">
                        <option value="">Select coach...</option>
                        @if (!empty($coachSuggestions))
                            <optgroup label="Taught this child">
                                @foreach ($coachSuggestions as $s)
                                    <option value="{{ $s['id'] }}">{{ $s['name'] }} ({{ $s['sessions'] }} session{{ $s['sessions'] > 1 ? 's' : '' }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Other coaches">
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
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Coach</label>
                        <div class="px-3.5 py-3 rounded-xl border border-line bg-off text-sm text-faint">Select an enrollment first</div>
                    </div>
                @endif

                <x-input wire:model="periodLabel" label="Period Label" placeholder="e.g. Jun–Jul 2026"
                         required :error="$errors->first('periodLabel')" />

                <div class="grid grid-cols-2 gap-4">
                    <x-input wire:model="periodStart" type="date" label="Start Date" required :error="$errors->first('periodStart')" />
                    <x-input wire:model="periodEnd" type="date" label="End Date" required :error="$errors->first('periodEnd')" />
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Overall Notes</label>
                    <textarea wire:model="overallNotes" rows="3" aria-label="Overall notes"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="General notes about the student..."></textarea>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="closeForm">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="create" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="create">Create</span>
                    <span wire:loading wire:target="create">Creating...</span>
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
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Publish Report Card</h3>
                <button wire:click="cancelPublish" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6">
                <p class="text-sm text-muted">This report card will be visible to parents immediately.</p>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelPublish">Cancel</x-btn>
                <x-btn variant="success" class="flex-1" wire:click="publish" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="publish">Yes, Publish</span>
                    <span wire:loading wire:target="publish">Publishing...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
