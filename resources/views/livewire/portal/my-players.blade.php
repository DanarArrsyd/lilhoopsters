<div>
    {{-- Header --}}
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">My Players</h2>
            <p class="text-sm text-muted">Manage your children's profiles.</p>
        </div>
        <x-btn wire:click="openCreate">+ Add Player</x-btn>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Players grid --}}
    @if ($children->isEmpty())
        <x-empty-state title="No players yet" description="Add your child to get started with enrollment." />
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($children as $child)
                @php $months = $child->ageInMonths(); @endphp
                <x-card padding="p-5">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-navy/8 text-navy flex items-center justify-center text-lg font-bold shrink-0">
                            {{ strtoupper(substr($child->name, 0, 1)) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="font-semibold text-ink">{{ $child->name }}</p>
                                <x-badge :status="$child->status">{{ ucfirst($child->status) }}</x-badge>
                            </div>
                            <p class="text-sm text-muted mt-0.5">
                                @if ($months >= 12)
                                    {{ floor($months / 12) }}yr {{ $months % 12 > 0 ? ($months % 12) . 'mo' : '' }}
                                @else
                                    {{ $months }}mo
                                @endif
                                &middot; {{ ucfirst($child->gender) }}
                            </p>
                            @if ($child->jersey_name || $child->jersey_number)
                                <p class="text-xs text-faint mt-1">
                                    Jersey: {{ $child->jersey_name ?? '—' }}
                                    @if ($child->jersey_number) #{{ $child->jersey_number }} @endif
                                </p>
                            @endif
                            @if ($child->school)
                                <p class="text-xs text-faint">{{ $child->school }}</p>
                            @endif
                        </div>
                    </div>

                    @if ($child->status === 'unregistered')
                        <div class="mt-4 p-3 bg-[#B45309]/8 rounded-xl border border-[#B45309]/20">
                            <p class="text-xs text-[#B45309] font-semibold">Not yet registered</p>
                            <p class="text-xs text-[#B45309]/80 mt-0.5">Go to <a href="{{ route('parent.enroll') }}" class="underline">Enroll Player</a> to start registration.</p>
                        </div>
                    @elseif ($child->status === 'pending')
                        <div class="mt-4 p-3 bg-[#1D4ED8]/8 rounded-xl border border-[#1D4ED8]/20">
                            <p class="text-xs text-[#1D4ED8] font-semibold">Waiting for admin approval</p>
                            <p class="text-xs text-[#1D4ED8]/80 mt-0.5">We'll notify you once approved.</p>
                        </div>
                    @endif

                    <div class="mt-4 flex items-center justify-between">
                        <span class="text-xs text-faint">{{ $child->enrollments_count }} enrollment(s)</span>
                        <div class="flex gap-2">
                            @if ($child->status === 'active')
                                <x-btn variant="ghost" size="sm" wire:click="openQr({{ $child->id }})" title="Show QR Code">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                    </svg>
                                    QR
                                </x-btn>
                            @endif
                            <x-btn variant="ghost" size="sm" wire:click="openEdit({{ $child->id }})">Edit</x-btn>
                        </div>
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif

    {{-- QR Code modal --}}
    @if ($showQr && $qrChildId)
        @php $qrChild = $children->find($qrChildId); @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-navy/40" wire:click="closeQr"></div>
            <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-xs text-center">
                <div class="flex items-center justify-between px-6 py-4 border-b border-line">
                    <h3 class="font-extrabold uppercase tracking-tight text-navy text-sm">QR Code — {{ $qrChild?->name }}</h3>
                    <button wire:click="closeQr" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
                </div>
                <div class="p-6">
                    <div class="inline-block p-3 bg-surface border-2 border-line rounded-xl">
                        {!! $qrSvg !!}
                    </div>
                    <p class="text-xs text-faint mt-3">Show this QR code to your coach at attendance time.</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Add / Edit modal --}}
    @if ($showForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-navy/40" wire:click="cancel"></div>
        <div class="relative bg-surface rounded-2xl border border-line shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-surface flex items-center justify-between px-6 py-4 border-b border-line z-10">
                <h3 class="text-lg font-extrabold uppercase tracking-tight text-navy">
                    {{ $editingId ? 'Edit Player' : 'Add New Player' }}
                </h3>
                <button wire:click="cancel" class="text-muted hover:text-navy p-1 leading-none">&#x2715;</button>
            </div>
            <div class="p-6 space-y-4">
                <x-input wire:model="name" label="Full Name" placeholder="e.g. Budi Santoso"
                         required :error="$errors->first('name')" />
                <x-input type="date" wire:model="birthDate" label="Date of Birth"
                         required :error="$errors->first('birthDate')" />
                <x-select wire:model="gender" label="Gender" :error="$errors->first('gender')">
                    <option value="">Select gender...</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </x-select>
                <x-input wire:model="school" label="School" placeholder="e.g. SD Negeri 01" />
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Medical Notes</label>
                    <textarea wire:model="medicalNotes" rows="2" aria-label="Medical notes"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="Allergies, conditions, etc. (optional)"></textarea>
                </div>

                @if ($editingId && $children->find($editingId)?->status === 'active')
                    <div class="border-t border-line pt-4">
                        <p class="text-xs font-bold uppercase tracking-wide text-navy mb-3">Jersey Info</p>
                        <div class="grid grid-cols-2 gap-3">
                            <x-input wire:model="jerseyName" label="Jersey Name" placeholder="e.g. BUDI" />
                            <x-input wire:model="jerseyNumber" label="Number" placeholder="e.g. 23" />
                        </div>
                    </div>
                @endif
            </div>
            <div class="flex gap-3 px-6 pb-6">
                <x-btn variant="secondary" class="flex-1" wire:click="cancel">Cancel</x-btn>
                <x-btn class="flex-1" wire:click="save" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save Changes' : 'Add Player' }}</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </x-btn>
            </div>
        </div>
    </div>
    @endif
</div>
