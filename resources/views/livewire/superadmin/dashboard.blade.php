<div class="space-y-6">

    <div>
        <h1 class="text-xl font-semibold text-slate-900">Super Admin Dashboard</h1>
        <p class="text-sm text-slate-500 mt-1">System overview</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Admins</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['active_admins'] }}<span class="text-sm font-normal text-slate-400">/{{ $stats['total_admins'] }}</span></p>
                <p class="text-xs text-slate-400 mt-1">active / total</p>
            </div>
        </x-card>
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Coaches</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_coaches'] }}</p>
                <p class="text-xs text-slate-400 mt-1">registered</p>
            </div>
        </x-card>
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Parents</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_parents'] }}</p>
                <p class="text-xs text-slate-400 mt-1">registered</p>
            </div>
        </x-card>
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Players</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['active_players'] }}<span class="text-sm font-normal text-slate-400">/{{ $stats['total_players'] }}</span></p>
                <p class="text-xs text-slate-400 mt-1">active / total</p>
            </div>
        </x-card>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <x-card>
            <div class="p-5">
                <p class="text-sm font-medium text-slate-700 mb-1">Active Enrollments</p>
                <p class="text-3xl font-bold text-orange-600">{{ $stats['enrollments'] }}</p>
            </div>
        </x-card>
        <x-card>
            <div class="p-5 space-y-3">
                <p class="text-sm font-medium text-slate-700">Quick Links</p>
                <div class="space-y-2">
                    <a href="{{ route('superadmin.admins') }}" class="flex items-center gap-2 text-sm text-orange-600 hover:text-orange-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Manage Admin Accounts
                    </a>
                    <a href="{{ route('superadmin.system-settings') }}" class="flex items-center gap-2 text-sm text-orange-600 hover:text-orange-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        System Settings
                    </a>
                </div>
            </div>
        </x-card>
    </div>

</div>
