<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Coach Check-In</h2>
        <p class="text-sm text-muted">Record your presence at a location.</p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Active check-in state --}}
    @if ($active)
        <x-card class="mb-6" padding="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 bg-navy/8 rounded-xl flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-[#15803D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-ink">Currently Checked In</p>
                    <p class="text-sm text-muted">{{ $active->location->name }}</p>
                </div>
                <x-badge status="active" class="ml-auto">Active</x-badge>
            </div>
            <div class="text-sm text-muted space-y-1 mb-5">
                <p>Checked in: <span class="font-semibold text-ink">{{ $active->checked_in_at->format('H:i, d M Y') }}</span></p>
                <p>Expires: <span class="font-semibold text-ink">{{ $active->expires_at->format('H:i, d M Y') }}</span></p>
            </div>
            <x-btn variant="danger" wire:click="checkOut" wire:loading.attr="disabled"
                   wire:confirm="Are you sure you want to check out?">
                <span wire:loading.remove wire:target="checkOut">Check Out</span>
                <span wire:loading wire:target="checkOut">Checking out...</span>
            </x-btn>
        </x-card>
    @else
        {{-- Check-in form --}}
        <x-card class="mb-6" padding="p-0">
            <div class="px-4 py-3 border-b border-line">
                <h3 class="text-sm font-bold uppercase tracking-wide text-navy">Check In Now</h3>
            </div>
            <div class="p-6 space-y-4">
                <x-select wire:model="locationId" label="Location" :error="$errors->first('locationId')">
                    <option value="">— Select location —</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="notes" label="Notes (optional)" placeholder="e.g. Substituting for coach Andi" />
            </div>
            <div class="px-6 pb-6">
                <x-btn wire:click="checkIn" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="checkIn">Check In</span>
                    <span wire:loading wire:target="checkIn">Checking in...</span>
                </x-btn>
            </div>
        </x-card>
    @endif

    {{-- History --}}
    @if ($history->isNotEmpty())
        <x-card padding="p-0">
            <div class="px-4 py-3 border-b border-line">
                <h3 class="text-sm font-bold uppercase tracking-wide text-navy">Recent History</h3>
            </div>
            <div class="divide-y divide-line">
                @foreach ($history as $h)
                    <div class="px-4 py-3 flex items-center justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold text-ink">{{ $h->location->name }}</p>
                            <p class="text-xs text-faint">{{ $h->checked_in_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="text-right text-xs">
                            @if ($h->checked_out_at)
                                <span class="text-faint">Out: {{ $h->checked_out_at->format('H:i') }}</span>
                            @elseif ($h->isActive())
                                <x-badge status="active">Active</x-badge>
                            @else
                                <span class="text-[#B91C1C]">Expired</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
</div>
