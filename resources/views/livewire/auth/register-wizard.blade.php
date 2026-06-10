<div>
    {{-- Progress bar --}}
    <div class="flex items-center gap-2 mb-3">
        @for ($i = 1; $i <= $totalSteps; $i++)
            <div class="flex-1 h-1 rounded-full {{ $i <= $step ? 'bg-navy' : 'bg-line' }}"></div>
        @endfor
    </div>
    <p class="text-xs font-semibold uppercase tracking-wide text-faint mb-6">Step {{ $step }} of {{ $totalSteps }}</p>

    {{-- Step 1: Account --}}
    @if ($step === 1)
        <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy mb-1">Create your account</h2>
        <p class="text-sm text-muted mb-6">Your sign-in details</p>
        <div class="space-y-4">
            <x-input wire:model="name" label="Full Name" placeholder="e.g. Budi Santoso" required :error="$errors->first('name')" />
            <x-input wire:model="email" type="email" label="Email" placeholder="you@example.com" required :error="$errors->first('email')" />
            <x-input wire:model="password" type="password" label="Password" placeholder="Min. 8 characters" required :error="$errors->first('password')" />
            <x-input wire:model="password_confirmation" type="password" label="Confirm Password" placeholder="Repeat your password" required :error="$errors->first('password_confirmation')" />
        </div>
        <x-btn wire:click="nextStep" class="w-full mt-6">Next →</x-btn>
    @endif

    {{-- Step 2: Parent Info --}}
    @if ($step === 2)
        <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy mb-1">Parent information</h2>
        <p class="text-sm text-muted mb-6">Your contact details</p>
        <div class="space-y-4">
            <x-input wire:model="whatsapp_number" label="WhatsApp Number" placeholder="e.g. 08123456789" required :error="$errors->first('whatsapp_number')" helper="We'll use this for important updates" />
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Address <span class="text-faint font-normal normal-case">(optional)</span></label>
                <textarea wire:model="address" rows="2" placeholder="e.g. Jl. Sudirman No. 1, Jakarta"
                          class="block w-full rounded-xl px-3.5 py-3 text-sm bg-surface text-ink border border-line placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy"></textarea>
            </div>
            <x-input wire:model="occupation" label="Occupation" placeholder="e.g. Business Owner" :error="$errors->first('occupation')" />
        </div>
        <div class="flex gap-3 mt-6">
            <x-btn wire:click="prevStep" variant="secondary" class="flex-1">← Back</x-btn>
            <x-btn wire:click="nextStep" class="flex-1">Next →</x-btn>
        </div>
    @endif

    {{-- Step 3: Player --}}
    @if ($step === 3)
        <h2 class="text-xl font-extrabold uppercase tracking-tight text-navy mb-1">Add your player</h2>
        <p class="text-sm text-muted mb-6">You can add more players after signing in</p>
        <div class="space-y-4">
            <x-input wire:model="child_name" label="Player's Name" placeholder="e.g. Rafi Santoso" required :error="$errors->first('child_name')" />
            <x-input wire:model="child_birth_date" type="date" label="Date of Birth" required :error="$errors->first('child_birth_date')" />
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Gender <span class="text-[#DC2626]">*</span></label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                        <input type="radio" wire:model="child_gender" value="male" class="accent-navy"> Boy
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer text-sm text-ink">
                        <input type="radio" wire:model="child_gender" value="female" class="accent-navy"> Girl
                    </label>
                </div>
                @error('child_gender') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
            </div>
            <x-input wire:model="child_school" label="School" placeholder="e.g. SPH Kemang" :error="$errors->first('child_school')" />
        </div>
        <x-alert type="info" class="mt-4 text-xs">
            After signing up, our team will review your registration within <strong>1–2 business days</strong>.
        </x-alert>
        <div class="flex gap-3 mt-6">
            <x-btn wire:click="prevStep" variant="secondary" class="flex-1">← Back</x-btn>
            <x-btn wire:click="submit" class="flex-1" wire:loading.attr="disabled">
                <span wire:loading.remove>Join Lil' Hoopsters!</span>
                <span wire:loading>Submitting...</span>
            </x-btn>
        </div>
    @endif
</div>
