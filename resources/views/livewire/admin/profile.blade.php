<div class="max-w-2xl space-y-6">

    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">{{ __('messages.admin.profile.title') }}</h2>
        <p class="text-sm text-muted">{{ __('messages.admin.profile.subtitle') }}</p>
    </div>

    {{-- Personal Information --}}
    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.personal_info') }}</p>
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
                    <p class="text-xs text-faint mt-0.5">{{ __('messages.admin.profile.email_readonly') }}</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.full_name') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input wire:model="name" placeholder="Your full name" />
                @error('name') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.whatsapp') }}</label>
                <x-input wire:model="whatsappNumber" placeholder="e.g. 08123456789" />
                @error('whatsappNumber') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.occupation') }}</label>
                <x-input wire:model="occupation" placeholder="e.g. Software Engineer" />
                @error('occupation') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.address') }}</label>
                <textarea wire:model="address" rows="2" aria-label="Address"
                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                          placeholder="Your home address"></textarea>
                @error('address') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn wire:click="saveProfile" wire:loading.attr="disabled">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="saveProfile">{{ __('messages.admin.profile.save_changes') }}</span>
                <span wire:loading wire:target="saveProfile">{{ __('messages.common.saving') }}</span>
            </x-btn>
        </div>
    </x-card>

    {{-- Change Password --}}
    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.change_password') }}</p>
        </div>

        @if (session('password_success'))
            <div class="px-6 pt-4">
                <x-alert type="success">{{ session('password_success') }}</x-alert>
            </div>
        @endif

        <div class="p-6 space-y-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.current_password') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="currentPassword" placeholder="Enter current password" />
                @error('currentPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.new_password') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="newPassword" placeholder="Min. 8 characters" />
                @error('newPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.admin.profile.confirm_password') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="newPasswordConfirmation" placeholder="Repeat new password" />
                @error('confirmPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn wire:click="changePassword" wire:loading.attr="disabled">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="changePassword">{{ __('messages.admin.profile.change_password') }}</span>
                <span wire:loading wire:target="changePassword">{{ __('messages.admin.profile.updating') }}</span>
            </x-btn>
        </div>
    </x-card>

    {{-- Payment Accounts --}}
    <livewire:admin.payment-accounts />

</div>
