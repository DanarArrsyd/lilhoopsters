<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Leave Requests</h2>
            <p class="text-sm text-slate-500">Submit and track sick/permit requests</p>
        </div>
        <x-btn variant="primary" wire:click="openCreate">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Request
        </x-btn>
    </div>

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    @if ($activeEnrollments->isEmpty())
        <x-card padding="p-8">
            <x-empty-state
                title="No active enrollments"
                description="You need an active program enrollment to submit a leave request." />
        </x-card>
    @else

        {{-- Filters --}}
        <x-card class="mb-4" padding="p-4">
            <div class="flex gap-3 flex-wrap">
                <div class="w-44">
                    <select wire:model.live="filterChildId"
                            class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                        <option value="">All Players</option>
                        @foreach ($children as $child)
                            <option value="{{ $child->id }}">{{ $child->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-40">
                    <select wire:model.live="filterStatus"
                            class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="auto_approved">Auto Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
            </div>
        </x-card>

        {{-- Table --}}
        <x-card>
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100">
                        <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Player</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Schedule</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Type</th>
                        <th class="text-left py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($leaveRequests as $lr)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-3 px-4 font-medium text-slate-900">{{ $lr->child->name }}</td>
                            <td class="py-3 px-4">
                                <p class="text-slate-700">{{ $lr->schedule->program->name ?? '—' }}</p>
                                <p class="text-xs text-slate-400">
                                    {{ ucfirst($lr->schedule->day_of_week ?? '') }} · {{ $lr->schedule->location->name ?? '' }}
                                </p>
                            </td>
                            <td class="py-3 px-4 text-slate-600">{{ $lr->leave_date->format('d M Y') }}</td>
                            <td class="py-3 px-4">
                                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                    {{ $lr->type === 'sick' ? 'bg-red-100 text-red-600' : 'bg-blue-100 text-blue-600' }}">
                                    {{ ucfirst($lr->type) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <x-badge :status="$lr->status">{{ ucwords(str_replace('_', ' ', $lr->status)) }}</x-badge>
                                @if ($lr->admin_notes)
                                    <p class="text-xs text-red-500 mt-0.5">{{ $lr->admin_notes }}</p>
                                @endif
                                @if ($lr->status === 'pending')
                                    <p class="text-xs text-slate-400 mt-0.5">
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
            @if ($leaveRequests->hasPages())
                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $leaveRequests->links() }}
                </div>
            @endif
        </x-card>

    @endif

    {{-- Form modal --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-900">Submit Leave Request</h3>
                </div>

                <div class="p-6 space-y-4">
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Player & Enrollment <span class="text-red-500">*</span></label>
                        <select wire:model="enrollmentId"
                                class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500">
                            <option value="">Select enrollment...</option>
                            @foreach ($activeEnrollments as $en)
                                <option value="{{ $en->id }}">
                                    {{ $en->child->name }} — {{ $en->schedule->program->name }}
                                    ({{ ucfirst($en->schedule->day_of_week) }}, {{ $en->schedule->location->name }})
                                </option>
                            @endforeach
                        </select>
                        @error('enrollmentId') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Leave Date <span class="text-red-500">*</span></label>
                        <x-input type="date" wire:model="leaveDate" />
                        @error('leaveDate') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Type <span class="text-red-500">*</span></label>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="type" value="sick"
                                       class="text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-slate-700">Sick (Sakit)</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" wire:model="type" value="permit"
                                       class="text-orange-500 focus:ring-orange-500">
                                <span class="text-sm text-slate-700">Permit (Izin)</span>
                            </label>
                        </div>
                        @error('type') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-slate-700">Reason</label>
                        <textarea wire:model="reason" rows="3"
                                  class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                                  placeholder="Briefly describe the reason (optional)..."></textarea>
                    </div>

                    <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
                        <p class="text-xs text-blue-700">Leave requests are auto-approved after 72 hours if not reviewed by admin.</p>
                    </div>
                </div>

                <div class="flex gap-3 px-6 pb-6">
                    <x-btn variant="secondary" class="flex-1 justify-center" wire:click="cancel">Cancel</x-btn>
                    <x-btn variant="primary" class="flex-1 justify-center" wire:click="submit"
                           wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="submit">Submit</span>
                        <span wire:loading wire:target="submit">Submitting...</span>
                    </x-btn>
                </div>
            </div>
        </div>
    @endif
</div>
