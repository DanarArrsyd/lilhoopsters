<div>
    <div class="mb-6">
        <h2 class="text-2xl font-extrabold uppercase tracking-tight text-navy">Dashboard</h2>
        <p class="text-sm text-muted">Overview of academy activity</p>
    </div>

    {{-- Action required --}}
    <h3 class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">Action Required</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        @php
            $actionCards = [
                ['route' => 'admin.parents',     'label' => 'Pending Registrations', 'count' => $stats['pending_registrations'], 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z'],
                ['route' => 'admin.enrollments', 'label' => 'Pending Enrollments',   'count' => $stats['pending_enrollments'],   'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['route' => 'admin.payments',    'label' => 'Pending Payments',      'count' => $stats['pending_payments'],       'icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
            ];
        @endphp
        @foreach ($actionCards as $c)
            <a href="{{ route($c['route']) }}" class="block">
                <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer border-l-4 {{ $c['count'] > 0 ? 'border-l-[#B45309]' : 'border-l-line' }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide">{{ $c['label'] }}</p>
                            <p class="text-3xl font-extrabold text-navy mt-1">{{ $c['count'] }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl {{ $c['count'] > 0 ? 'bg-[#B45309]/10' : 'bg-navy/5' }} flex items-center justify-center">
                            <svg class="w-5 h-5 {{ $c['count'] > 0 ? 'text-[#B45309]' : 'text-faint' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/>
                            </svg>
                        </div>
                    </div>
                    @if ($c['count'] > 0)
                        <p class="text-xs text-[#B45309] mt-2 font-semibold">Needs review →</p>
                    @endif
                </x-card>
            </a>
        @endforeach
    </div>

    {{-- Overview --}}
    <h3 class="text-[10px] font-bold uppercase tracking-widest text-faint mb-3">Overview</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        @php
            $overviewCards = [
                ['route' => 'admin.players',   'label' => 'Active Players',   'count' => $stats['active_players'],   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
                ['route' => 'admin.locations', 'label' => 'Active Locations', 'count' => $stats['active_locations'], 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z'],
                ['route' => 'admin.coaches',   'label' => 'Active Coaches',   'count' => $stats['active_coaches'],   'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
            ];
        @endphp
        @foreach ($overviewCards as $c)
            <a href="{{ route($c['route']) }}" class="block">
                <x-card padding="p-5" class="hover:shadow-md transition-shadow cursor-pointer">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-muted uppercase tracking-wide">{{ $c['label'] }}</p>
                            <p class="text-3xl font-extrabold text-navy mt-1">{{ $c['count'] }}</p>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-navy/8 flex items-center justify-center">
                            <svg class="w-5 h-5 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/>
                            </svg>
                        </div>
                    </div>
                </x-card>
            </a>
        @endforeach
    </div>
</div>
