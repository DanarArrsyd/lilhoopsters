<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">System Settings</h2>
        <p class="text-sm text-muted">Configure academy information and system defaults.</p>
    </div>

    {{-- Flash --}}
    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    {{-- Academy Information --}}
    <x-card class="mb-4" padding="p-6">
        <p class="text-xs font-bold uppercase tracking-wide text-navy mb-5">Academy Information</p>
        <div class="space-y-4">
            <div class="grid sm:grid-cols-2 gap-4">
                <x-input wire:model="academyName" label="Academy Name" placeholder="e.g. Lil' Hoopsters Basketball Academy"
                         required :error="$errors->first('academyName')" />
                <x-input type="email" wire:model="academyEmail" label="Email" placeholder="info@academy.com"
                         :error="$errors->first('academyEmail')" />
                <x-input wire:model="academyPhone" label="Phone / WhatsApp" placeholder="+62 812 xxxx xxxx" />
                <x-input wire:model="academyWebsite" label="Website" placeholder="https://..." />
            </div>
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Address</label>
                <textarea wire:model="academyAddress" rows="2" aria-label="Academy address"
                          class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                          placeholder="Academy address..."></textarea>
            </div>
        </div>
    </x-card>

    {{-- System Defaults --}}
    <x-card class="mb-6" padding="p-6">
        <p class="text-xs font-bold uppercase tracking-wide text-navy mb-5">System Defaults</p>
        <div class="grid sm:grid-cols-2 gap-4">
            <x-select wire:model="currency" label="Currency" :error="$errors->first('currency')">
                <option value="IDR">IDR — Indonesian Rupiah</option>
                <option value="USD">USD — US Dollar</option>
                <option value="SGD">SGD — Singapore Dollar</option>
                <option value="MYR">MYR — Malaysian Ringgit</option>
            </x-select>
            <x-select wire:model="timezone" label="Timezone" :error="$errors->first('timezone')">
                <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                <option value="Asia/Singapore">Asia/Singapore</option>
                <option value="Asia/Kuala_Lumpur">Asia/Kuala_Lumpur</option>
                <option value="UTC">UTC</option>
            </x-select>
        </div>
    </x-card>

    <x-btn wire:click="save" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="save">Save Settings</span>
        <span wire:loading wire:target="save">Saving...</span>
    </x-btn>
</div>
