<x-app>
    <x-slot name="title">{{ $title ?? 'Login' }}</x-slot>

    <div class="min-h-screen flex items-center justify-center p-4 bg-gradient-to-br from-[#0F1F38] to-[#1E3A5F]">
        <div class="w-full max-w-md">
            {{-- Logo --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center gap-3">
                    <div class="w-10 h-10 bg-orange-500 rounded-xl flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
                            <path d="M12 2C12 2 8 7 8 12s4 10 4 10" stroke="currentColor" stroke-width="1.5" fill="none"/>
                            <path d="M12 2C12 2 16 7 16 12s-4 10-4 10" stroke="currentColor" stroke-width="1.5" fill="none"/>
                            <path d="M2 12h20" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                    </div>
                    <span class="text-white font-bold text-xl">BasketManage</span>
                </div>
                <p class="text-slate-400 text-sm mt-1">Lil' Hoopsters Basketball Academy</p>
            </div>

            {{-- Card --}}
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-app>
