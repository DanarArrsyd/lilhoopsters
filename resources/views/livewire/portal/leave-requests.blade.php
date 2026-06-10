<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Leave Requests</h2>
            <p class="text-sm text-muted">Submit and track sick/permit requests.</p>
        </div>
        <x-btn wire:click="openCreate">+ New Request</x-btn>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if ($activeEnrollments->isEmpty())
        <x-empty-state title="No active enrollments" description="You need an active program enrollment to submit a leave request." />
    @else
        {{-- Filters --}}
        <x-card class="mb-4" padding="p-4">
            <div class="flex gap-3 flex-wrap">
                <div class="w-44">
                    <x-select wire:model.live="filterChildId">
                        <option value="">All Players</option>
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}">{{ $child->name }}</option>
                        @endforeach
                    </x-select>
                </div>
                <div class="w-40">
                    <x-select wire:model.live="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="auto_approved">Auto Approved</option>
                        <option value="rejected">Rejected</option>
                    </x-select>
                </div>
            </div>
        </x-card>

        {{-- Table --}}
        <x-card padding="p-0">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[560px]">
                    <thead>
                        <tr class="border-b border-line">
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Player</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Schedule</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Date</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Type</th>
                            <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($leaveRequests as $lr)
                            <tr class="hover:bg-off transition-colors">
                                <td class="py-3 px-4 font-semibold text-ink">{{ $lr->child->name }}</td>
                                <td class="py-3 px-4">
                                    <p class="text-ink">{{ $lr->schedule->program->name ?? '—' }}</p>
                                    <p class="text-xs text-faint">
                                        {{ ucfirst($lr->schedule->day_of_week ?? '') }} · {{ $lr->schedule->location->name ?? '' }}
                                    </p>
                                </td>
                                <td class="py-3 px-4 text-ink">{{ $lr->leave_date->format('d M Y') }}</td>
                                <td class="py-3 px-4">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-semibold bg-navy/8 text-navy capitalize">
                                        {{ ucfirst($lr->type) }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <x-badge :status="$lr->status">{{ ucwords(str_replace('_', ' ', $lr->status)) }}</x-badge>
                                    @if ($lr->admin_notes)
                                        <p class="text-xs text-[#B91C1C] mt-0.5">{{ $lr->admin_notes }}</p>
                                    @endif
                                    @if ($lr->status === 'pending')
                                        <p class="text-xs text-faint mt-0.5">
                                            Auto-approved {{ $lr->auto_approve_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-2">
                                    <x-empty-state title="No leave requests yet" description="Submit a request using the button above." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($leaveRequests->hasPages())
                <div class="px-4 py-3 border-t border-line">
                    {{ $leaveRequests->links() }}
                </div>
            @endif
        </x-card>
    @endif

    {{-- Form modal --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="cancel"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">Submit Leave Request</h3>
                <button wire:click="cancel" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model="enrollmentId" label="Player & Enrollment"
                          :error="$errors->first('enrollmentId')">
                    <option value="">Select enrollment...</option>
                    @foreach ($activeEnrollments as $en)
                        <option value="{{ $en->id }}">
                            {{ $en->child->name }} — {{ $en->schedule->program->name }}
                            ({{ ucfirst($en->schedule->day_of_week) }}, {{ $en->schedule->location->name }})
                        </option>
                    @endforeach
                </x-select>

                <x-input type="date" wire:model="leaveDate" label="Leave Date"
                         required :error="$errors->first('leaveDate')" />

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Type <span class="text-[#B91C1C]">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                            <input type="radio" wire:model="type" value="sick" class="accent-navy">
                            Sick
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                            <input type="radio" wire:model="type" value="permit" class="accent-navy">
                            Permit
                        </label>
                    </div>
                    @error('type') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Reason</label>
                    <textarea wire:model="reason" rows="3" aria-label="Leave reason"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="Briefly describe the reason (optional)..."></textarea>
                </div>

                <div class="p-3 bg-navy/8 rounded-xl border border-navy/20">
                    <p class="text-xs text-navy font-semibold">Auto-approval in 72 hours</p>
                    <p class="text-xs text-navy/70 mt-0.5">Leave requests are auto-approved if not reviewed by admin within 72 hours.</p>
                </div>
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancel">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Submit</span>
                    <span wire:loading wire:target="submit">Submitting...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
