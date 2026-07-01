<div class="max-w-6xl mx-auto">

    <x-admin.page-header :title="__('messages.coach.profile.title')" :subtitle="__('messages.coach.profile.subtitle')" />

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start">
    <div class="space-y-6 min-w-0">

    {{-- Personal Information --}}
    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.personal_info') }}</p>
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
                    <p class="text-xs text-faint mt-0.5">{{ __('messages.coach.profile.email_readonly') }}</p>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.full_name') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input wire:model="name" placeholder="Your full name" />
                @error('name') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.whatsapp') }}</label>
                    <x-input wire:model="whatsappNumber" placeholder="e.g. 08123456789" />
                    @error('whatsappNumber') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.phone') }}</label>
                    <x-input wire:model="phone" placeholder="e.g. 08123456789" />
                    @error('phone') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.specialization') }}</label>
                <x-input wire:model="specialization" placeholder="e.g. Shooting & Defense" />
                @error('specialization') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.address') }}</label>
                <textarea wire:model="address" rows="2" aria-label="Address"
                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                          placeholder="Your home address"></textarea>
                @error('address') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn wire:click="saveProfile" wire:loading.attr="disabled">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="saveProfile">{{ __('messages.coach.profile.save_changes') }}</span>
                <span wire:loading wire:target="saveProfile">{{ __('messages.common.saving') }}</span>
            </x-btn>
        </div>
    </x-card>

    {{-- Change Password --}}
    <x-card padding="p-0">
        <div class="px-6 py-4 border-b border-line">
            <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.change_password') }}</p>
        </div>

        @if (session('password_success'))
            <div class="px-6 pt-4">
                <x-alert type="success">{{ session('password_success') }}</x-alert>
            </div>
        @endif

        <div class="p-6 space-y-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.current_password') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="currentPassword" placeholder="Enter current password" />
                @error('currentPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.new_password') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="newPassword" placeholder="Min. 8 characters" />
                @error('newPassword') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.confirm_password') }} <span class="text-[#B91C1C]">*</span></label>
                <x-input type="password" wire:model="newPasswordConfirmation" placeholder="Repeat new password" />
                @error('newPasswordConfirmation') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn wire:click="changePassword" wire:loading.attr="disabled">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="changePassword">{{ __('messages.coach.profile.change_password') }}</span>
                <span wire:loading wire:target="changePassword">{{ __('messages.coach.profile.updating') }}</span>
            </x-btn>
        </div>
    </x-card>

    </div>

    {{-- Right column --}}
    <div class="hidden lg:block lg:sticky lg:top-20 space-y-6">

        <x-card padding="p-0">
            <div class="px-6 py-4 border-b border-line">
                <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.account_overview') }}</p>
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
                        <span class="text-sm text-muted">{{ __('messages.coach.profile.role') }}</span>
                        <span class="text-sm font-semibold text-ink">{{ __('messages.coach.profile.role_coach') }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-sm text-muted">{{ __('messages.coach.profile.member_since') }}</span>
                        <span class="text-sm font-semibold text-ink">{{ auth()->user()->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>
        </x-card>

        <x-card padding="p-0">
            <div class="px-6 py-4 border-b border-line">
                <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.coach.profile.quick_links') }}</p>
            </div>
            <div class="p-2">
                <a href="{{ route('coach.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                    <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    {{ __('messages.nav.dashboard') }}
                </a>
                <a href="{{ route('coach.schedules') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                    <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ __('messages.coach.nav.schedules') }}
                </a>
                <a href="{{ route('coach.report-cards') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                    <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('messages.nav.report_cards') }}
                </a>
            </div>
        </x-card>

    </div>
    </div>
</div>
