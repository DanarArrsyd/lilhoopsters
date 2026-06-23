<div class="max-w-2xl space-y-6">

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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">WhatsApp Number</label>
                    <x-input wire:model="whatsappNumber" placeholder="e.g. 08123456789" />
                    @error('whatsappNumber') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Phone Number</label>
                    <x-input wire:model="phone" placeholder="e.g. 08123456789" />
                    @error('phone') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Specialization</label>
                <x-input wire:model="specialization" placeholder="e.g. Shooting & Defense" />
                @error('specialization') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
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
                @error('newPasswordConfirmation') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
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

</div>
