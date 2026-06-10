<div>
    <div class="mb-6">
        <h2 class="text-lg font-semibold text-slate-900">Dashboard</h2>
        <p class="text-sm text-slate-500">Overview of academy activity</p>
    </div>

    {{-- Action required --}}
    <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Action Required</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <a href="{{ route('admin.parents') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer border-l-4 {{ $stats['pending_registrations'] > 0 ? 'border-l-amber-400' : 'border-l-slate-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Registrations</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['pending_registrations'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full {{ $stats['pending_registrations'] > 0 ? 'bg-amber-100' : 'bg-slate-100' }} flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $stats['pending_registrations'] > 0 ? 'text-amber-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                </div>
                @if ($stats['pending_registrations'] > 0)
                    <p class="text-xs text-amber-600 mt-2 font-medium">Needs review →</p>
                @endif
            </x-card>
        </a>

        <a href="{{ route('admin.enrollments') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer border-l-4 {{ $stats['pending_enrollments'] > 0 ? 'border-l-amber-400' : 'border-l-slate-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Enrollments</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['pending_enrollments'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full {{ $stats['pending_enrollments'] > 0 ? 'bg-amber-100' : 'bg-slate-100' }} flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $stats['pending_enrollments'] > 0 ? 'text-amber-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                @if ($stats['pending_enrollments'] > 0)
                    <p class="text-xs text-amber-600 mt-2 font-medium">Needs review →</p>
                @endif
            </x-card>
        </a>

        <a href="{{ route('admin.payments') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer border-l-4 {{ $stats['pending_payments'] > 0 ? 'border-l-amber-400' : 'border-l-slate-200' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Pending Payments</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['pending_payments'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full {{ $stats['pending_payments'] > 0 ? 'bg-amber-100' : 'bg-slate-100' }} flex items-center justify-center">
                        <svg class="w-5 h-5 {{ $stats['pending_payments'] > 0 ? 'text-amber-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
                @if ($stats['pending_payments'] > 0)
                    <p class="text-xs text-amber-600 mt-2 font-medium">Needs review →</p>
                @endif
            </x-card>
        </a>
    </div>

    {{-- Overview --}}
    <h3 class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Overview</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <a href="{{ route('admin.players') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active Players</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['active_players'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                </div>
            </x-card>
        </a>

        <a href="{{ route('admin.locations') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active Locations</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['active_locations'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                    </div>
                </div>
            </x-card>
        </a>

        <a href="{{ route('admin.coaches') }}" class="block">
            <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Active Coaches</p>
                        <p class="text-3xl font-bold text-slate-900 mt-1">{{ $stats['active_coaches'] }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>
            </x-card>
        </a>
    </div>
</div>
