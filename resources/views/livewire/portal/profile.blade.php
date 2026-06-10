<div class="max-w-2xl space-y-6">

    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Profile Settings</h2>
        <p class="text-sm text-slate-500">Manage your account information</p>
    </div>

    {{-- Profile info --}}
    <x-card>
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900 text-sm">Personal Information</h3>
        </div>

        @if (session('profile_success'))
            <div class="px-6 pt-4">
                <x-alert type="success">{{ session('profile_success') }}</x-alert>
            </div>
        @endif

        <div class="p-6 space-y-4">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 bg-orange-500/20 rounded-full flex items-center justify-center text-orange-500 font-bold text-xl shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div>
                    <p class="font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-slate-500">{{ auth()->user()->email }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Email cannot be changed</p>
                </div>
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Full Name <span class="text-red-500">*</span></label>
                <x-input wire:model="name" placeholder="Your full name" />
                @error('name') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">WhatsApp Number</label>
                <x-input wire:model="whatsappNumber" placeholder="e.g. 08123456789" />
                @error('whatsappNumber') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Occupation</label>
                <x-input wire:model="occupation" placeholder="e.g. Software Engineer" />
                @error('occupation') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Address</label>
                <textarea wire:model="address" rows="2"
                          class="block w-full border border-slate-200 rounded-lg px-3 py-2 text-sm bg-white text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500/20 focus:border-orange-500"
                          placeholder="Your home address"></textarea>
                @error('address') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn variant="primary" wire:click="saveProfile" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="saveProfile">Save Changes</span>
                <span wire:loading wire:target="saveProfile">Saving...</span>
            </x-btn>
        </div>
    </x-card>

    {{-- Password change --}}
    <x-card>
        <div class="px-6 py-4 border-b border-slate-100">
            <h3 class="font-semibold text-slate-900 text-sm">Change Password</h3>
        </div>

        @if (session('password_success'))
            <div class="px-6 pt-4">
                <x-alert type="success">{{ session('password_success') }}</x-alert>
            </div>
        @endif

        <div class="p-6 space-y-4">
            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Current Password <span class="text-red-500">*</span></label>
                <x-input type="password" wire:model="currentPassword" placeholder="Enter current password" />
                @error('currentPassword') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">New Password <span class="text-red-500">*</span></label>
                <x-input type="password" wire:model="newPassword" placeholder="Min. 8 characters" />
                @error('newPassword') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div class="space-y-1">
                <label class="block text-sm font-medium text-slate-700">Confirm New Password <span class="text-red-500">*</span></label>
                <x-input type="password" wire:model="newPasswordConfirmation" placeholder="Repeat new password" />
                @error('confirmPassword') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="px-6 pb-6">
            <x-btn variant="primary" wire:click="changePassword" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="changePassword">Change Password</span>
                <span wire:loading wire:target="changePassword">Updating...</span>
            </x-btn>
        </div>
    </x-card>

</div>
