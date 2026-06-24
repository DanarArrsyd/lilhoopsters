<div class="min-h-[100dvh] bg-off flex flex-col items-center px-4 py-10">
    <div class="w-full max-w-xl">

        {{-- Brand --}}
        <div class="flex items-center gap-3 justify-center mb-8">
            <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-11 h-11 rounded-xl object-cover">
            <div>
                <p class="text-navy font-extrabold text-base uppercase tracking-tight leading-tight">Lil' Hoopsters</p>
                <p class="text-faint text-[11px] uppercase tracking-wide">Basketball Academy</p>
            </div>
        </div>

        @if ($submitted)
            <div class="bg-surface border border-line rounded-2xl p-8 text-center">
                <div class="w-14 h-14 mx-auto rounded-full bg-[#15803D]/10 flex items-center justify-center mb-4">
                    <svg class="w-7 h-7 text-[#15803D]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-xl font-extrabold text-navy">Thank you!</h1>
                <p class="text-sm text-muted mt-2">We've received your trial request. Our team will contact you on WhatsApp shortly to arrange the session.</p>
                <a href="{{ route('login') }}" class="inline-block mt-6 text-sm font-semibold text-navy hover:underline">Back to sign in</a>
            </div>
        @else
            <div class="text-center mb-6">
                <h1 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Book a Free Trial</h1>
                <p class="text-sm text-muted mt-1">Fill in the form and we'll reach out to schedule your child's trial session.</p>
            </div>

            <form wire:submit="submit" class="bg-surface border border-line rounded-2xl p-6 space-y-4">
                {{-- Honeypot (hidden from humans) --}}
                <div class="hidden" aria-hidden="true">
                    <label>Website<input type="text" wire:model="website" tabindex="-1" autocomplete="off"></label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input wire:model="parent_name" label="Parent Name" placeholder="Your full name" required :error="$errors->first('parent_name')" />
                    <x-input wire:model="whatsapp" label="WhatsApp" placeholder="08xxxxxxxxxx" required :error="$errors->first('whatsapp')" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input wire:model="child_name" label="Child Name" placeholder="Your child's name" required :error="$errors->first('child_name')" />
                    <x-input wire:model="child_age" type="number" label="Child Age" placeholder="e.g. 7" :error="$errors->first('child_age')" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Preferred Location</label>
                        <x-select wire:model="location_id">
                            <option value="">No preference</option>
                            @foreach ($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                            @endforeach
                        </x-select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Program of Interest</label>
                        <x-select wire:model="program_id">
                            <option value="">No preference</option>
                            @foreach ($programs as $prog)
                                <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <x-input wire:model="trial_date" type="date" label="Preferred Trial Date" :error="$errors->first('trial_date')" />

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold uppercase tracking-wide text-navy">Notes</label>
                    <textarea wire:model="notes" rows="3" aria-label="Notes"
                              class="block w-full rounded-xl border border-line bg-surface px-3.5 py-3 text-sm text-ink placeholder:text-faint focus:outline-none focus:ring-2 focus:ring-navy/15 focus:border-navy resize-none"
                              placeholder="Anything we should know? (optional)"></textarea>
                    @error('notes') <p class="text-xs text-[#B91C1C]">{{ $message }}</p> @enderror
                </div>

                <x-btn type="submit" class="w-full" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Request Trial</span>
                    <span wire:loading wire:target="submit">Sending...</span>
                </x-btn>

                <p class="text-center text-xs text-faint">
                    Already a member? <a href="{{ route('login') }}" class="font-semibold text-navy hover:underline">Sign in</a>
                </p>
            </form>
        @endif
    </div>
</div>
