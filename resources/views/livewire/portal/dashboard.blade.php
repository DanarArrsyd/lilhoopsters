<div>
    {{-- Header --}}
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Dashboard</h2>
        <p class="text-sm text-muted">Welcome back, {{ auth()->user()->name }} — here's what's happening with your players.</p>
    </div>

    {{-- Needs Attention --}}
    @if ($stats['pending_enrollments'] > 0 || $stats['pending_payments'] > 0 || $stats['pending_children'] > 0)
        <p class="text-xs font-bold uppercase tracking-widest text-muted mb-3">Needs Attention</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">

            @if ($stats['pending_children'] > 0)
                <a href="{{ route('parent.players') }}" class="block">
                    <x-card padding="p-5" class="hover:shadow-md transition-shadow border-l-4 border-l-[#B45309]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-muted uppercase tracking-wide">Pending Registration</p>
                                <p class="text-3xl font-extrabold text-navy mt-1">{{ $stats['pending_children'] }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-navy/8 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#B45309]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-[#B45309] mt-2 font-semibold">Waiting for admin approval →</p>
                    </x-card>
                </a>
            @endif

            @if ($stats['pending_enrollments'] > 0)
                <a href="{{ route('parent.enroll') }}" class="block">
                    <x-card padding="p-5" class="hover:shadow-md transition-shadow border-l-4 border-l-[#B45309]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-muted uppercase tracking-wide">Pending Enrollments</p>
                                <p class="text-3xl font-extrabold text-navy mt-1">{{ $stats['pending_enrollments'] }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-navy/8 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#B45309]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-[#B45309] mt-2 font-semibold">Waiting for admin approval →</p>
                    </x-card>
                </a>
            @endif

            @if ($stats['pending_payments'] > 0)
                <a href="{{ route('parent.payments') }}" class="block">
                    <x-card padding="p-5" class="hover:shadow-md transition-shadow border-l-4 border-l-[#B45309]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-muted uppercase tracking-wide">Pending Payments</p>
                                <p class="text-3xl font-extrabold text-navy mt-1">{{ $stats['pending_payments'] }}</p>
                            </div>
                            <div class="w-10 h-10 rounded-full bg-navy/8 flex items-center justify-center">
                                <svg class="w-5 h-5 text-[#B45309]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-[#B45309] mt-2 font-semibold">Upload payment proof →</p>
                    </x-card>
                </a>
            @endif

        </div>
    @endif

    {{-- Overview --}}
    <p class="text-xs font-bold uppercase tracking-widest text-muted mb-3">Overview</p>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <a href="{{ route('parent.players') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-muted uppercase tracking-wide">Active Players</p>
                        <p class="text-3xl font-extrabold text-navy mt-1">{{ $stats['active_children'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-navy/8 flex items-center justify-center">
                        <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
            </x-card>
        </a>

        <a href="{{ route('parent.enroll') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-muted uppercase tracking-wide">Active Enrollments</p>
                        <p class="text-3xl font-extrabold text-navy mt-1">{{ $stats['active_enrollments'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-navy/8 flex items-center justify-center">
                        <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                </div>
            </x-card>
        </a>

        <a href="{{ route('parent.payments') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-muted uppercase tracking-wide">Total Payments</p>
                        <p class="text-3xl font-extrabold text-navy mt-1">{{ $stats['paid_payments'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-navy/8 flex items-center justify-center">
                        <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </x-card>
        </a>

    </div>
</div>
