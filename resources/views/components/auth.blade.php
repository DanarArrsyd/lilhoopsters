<x-app>
    <x-slot name="title">{{ $title ?? 'Sign In' }}</x-slot>

    <div class="min-h-[100dvh] flex items-center justify-center p-4 bg-off">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3">
                    <div class="w-12 h-12 bg-navy rounded-2xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-off" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                            <path d="M12 2C12 2 8 7 8 12s4 10 4 10" stroke-width="1.5"/>
                            <path d="M12 2C12 2 16 7 16 12s-4 10-4 10" stroke-width="1.5"/>
                            <path d="M2 12h20" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <span class="text-navy font-extrabold text-2xl uppercase tracking-tight">BasketManage</span>
                </div>
                <p class="text-muted text-sm mt-2">Lil' Hoopsters Basketball Academy</p>
            </div>

            {{-- Card --}}
            <div class="bg-surface border border-line rounded-2xl shadow-sm p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-app>
