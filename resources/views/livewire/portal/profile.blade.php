<div class="lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start">
<div class="lg:col-span-2 max-w-2xl space-y-6">

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Profile Settings</h2>
        <p class="text-sm text-muted">Manage your account information.</p>
    </div>

    {{-- Personal Information --}}
    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">Personal Information</p>
        </div>

        @if (session('profile_success'))
            <div class="px-6 pt-4">
                <x-alert type="success">{{ session('profile_success') }}</x-alert>
            </div>
        @endif

        <div class="p-6 space-y-4">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 bg-navy/8 rounded-full flex items-center justify-center text-navy font-bold text-xl shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-semibold text-ink">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-muted">{{ auth()->user()->email }}</p>
                    <p class="text-xs text-faint mt-0.5">Email cannot be changed</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Full Name <span class="text-[#B91C1C]">*</span></label>
                <x-input wire:model="name" placeholder="Your full name" />
                @error('name') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">WhatsApp Number</label>
                <x-input wire:model="whatsappNumber" placeholder="e.g. 08123456789" />
                @error('whatsappNumber') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Occupation</label>
                <x-input wire:model="occupation" placeholder="e.g. Software Engineer" />
                @error('occupation') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Address</label>
                <textarea wire:model="address" rows="2" aria-label="Address"
                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                          placeholder="Your home address"></textarea>
                @error('address') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn wire:click="saveProfile" wire:loading.attr="disabled">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="saveProfile">Save Changes</span>
                <span wire:loading wire:target="saveProfile">Saving...</span>
            </x-btn>
        </div>
    </x-card>

    {{-- Change Password --}}
    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">Change Password</p>
        </div>

        @if (session('password_success'))
            <div class="px-6 pt-4">
                <x-alert type="success">{{ session('password_success') }}</x-alert>
            </div>
        @endif

        <div class="p-6 space-y-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Current Password <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="currentPassword" placeholder="Enter current password" />
                @error('currentPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">New Password <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="newPassword" placeholder="Min. 8 characters" />
                @error('newPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Confirm New Password <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="newPasswordConfirmation" placeholder="Repeat new password" />
                @error('confirmPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn wire:click="changePassword" wire:loading.attr="disabled">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="changePassword">Change Password</span>
                <span wire:loading wire:target="changePassword">Updating...</span>
            </x-btn>
        </div>
    </x-card>

    {{-- Sign Out --}}
    <x-card padding="p-0">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center justify-between px-6 py-4 text-left text-[#B91C1C] hover:bg-[#B91C1C]/5 transition-colors rounded-2xl">
                <span class="text-sm font-semibold">Sign Out</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>
    </x-card>

</div>

{{-- Right column --}}
<div class="hidden lg:block lg:sticky lg:top-20 space-y-6">

    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">Account Overview</p>
        </div>
        <div class="p-6 space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-navy/8 rounded-full flex items-center justify-center text-navy font-bold shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0">
                    <p class="font-semibold text-ink truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-muted truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <div class="divide-y divide-line border-t border-line">
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-muted">Linked players</span>
                    <span class="text-sm font-semibold text-ink">{{ auth()->user()->children()->count() }}</span>
                </div>
                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm text-muted">Member since</span>
                    <span class="text-sm font-semibold text-ink">{{ auth()->user()->created_at->format('M Y') }}</span>
                </div>
            </div>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">Quick Links</p>
        </div>
        <div class="p-2">
            <a href="{{ route('parent.players') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                My Players
            </a>
            <a href="{{ route('parent.payments') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m0-2c-1.11 0-2.08-.402-2.599-1"/></svg>
                Payments
            </a>
            <a href="{{ route('parent.guide') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s4.332.477 5.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                User Guide
            </a>
        </div>
    </x-card>

</div>
</div>
