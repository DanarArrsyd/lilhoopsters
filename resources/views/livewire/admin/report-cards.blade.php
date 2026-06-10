<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Report Cards</h2>
            <p class="text-sm text-muted">Manage and publish student report cards.</p>
        </div>
        <x-btn wire:click="openForm">+ New Report Card</x-btn>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Filters --}}
    <x-card class="mb-4" padding="p-4">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <x-input wire:model.live.debounce.300ms="search" placeholder="Search by child name..." />
            </div>
            <div class="w-full sm:w-44">
                <x-select wire:model.live="filterStatus">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="submitted">Submitted</option>
                    <option value="published">Published</option>
                </x-select>
            </div>
        </div>
    </x-card>

    {{-- Table --}}
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
                            <td class="py-3 px-4 text-muted">{{ $card->coach->user->name }}</td>
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
                            <td class="py-3 px-4">
                                @if ($card->status === 'submitted')
                                    <x-btn variant="primary" size="sm"
                                           wire:click="confirmPublish({{ $card->id }})"
                                           wire:loading.attr="disabled">Publish</x-btn>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-2">
                                <x-empty-state title="No report cards yet" description="Create the first report card for a student." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cards->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $cards->links() }}
            </div>
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
                    <x-select wire:model="enrollmentId" label="Enrollment" :error="$errors->first('enrollmentId')">
                        <option value="">Select enrollment...</option>
                        @foreach ($enrollments as $enrollment)
                            <option value="{{ $enrollment->id }}">{{ $enrollment->schedule?->program?->name ?? 'Program' }}</option>
                        @endforeach
                    </x-select>
                @endif

                <x-select wire:model="coachId" label="Coach" :error="$errors->first('coachId')">
                    <option value="">Select coach...</option>
                    @foreach ($coaches as $coach)
                        <option value="{{ $coach->id }}">{{ $coach->user->name }}</option>
                    @endforeach
                </x-select>

                <x-input wire:model="periodLabel" label="Period Label" placeholder="e.g. Q1 2025"
                         required :error="$errors->first('periodLabel')" />

                <div class="grid grid-cols-2 gap-4">
                    <x-input wire:model="periodStart" type="date" label="Start Date"
                             required :error="$errors->first('periodStart')" />
                    <x-input wire:model="periodEnd" type="date" label="End Date"
                             required :error="$errors->first('periodEnd')" />
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Overall Notes</label>
                    <textarea wire:model="overallNotes" rows="3" aria-label="Overall notes for report card"
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
                <p class="text-sm text-muted">Are you sure you want to publish this report card? Parents will be able to see it immediately.</p>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancelPublish">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="publish" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="publish">Yes, Publish</span>
                    <span wire:loading wire:target="publish">Publishing...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
