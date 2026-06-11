<x-app>
    <x-slot name="title">{{ $title ?? 'Sign In' }}</x-slot>

    <div class="min-h-[100dvh] flex items-center justify-center p-4 bg-off">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3">
                    <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-12 h-12 rounded-2xl object-cover">
                    <span class="text-navy font-extrabold text-2xl uppercase tracking-tight">Lil' Hoopsters</span>
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
