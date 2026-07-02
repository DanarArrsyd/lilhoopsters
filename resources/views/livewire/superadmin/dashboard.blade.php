<div class="max-w-6xl mx-auto space-y-6">

    <x-admin.page-header :title="__('messages.superadmin.nav.dashboard')" :subtitle="__('messages.superadmin.dashboard.subtitle')" />

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-muted uppercase tracking-wide">{{ __('messages.superadmin.dashboard.admins') }}</p>
                <p class="text-2xl font-bold text-navy mt-1">{{ $stats['active_admins'] }}<span class="text-sm font-normal text-faint">/{{ $stats['total_admins'] }}</span></p>
                <p class="text-xs text-faint mt-1">{{ __('messages.superadmin.dashboard.active_total') }}</p>
            </div>
        </x-card>
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-muted uppercase tracking-wide">{{ __('messages.superadmin.dashboard.coaches') }}</p>
                <p class="text-2xl font-bold text-navy mt-1">{{ $stats['total_coaches'] }}</p>
                <p class="text-xs text-faint mt-1">{{ __('messages.superadmin.dashboard.registered') }}</p>
            </div>
        </x-card>
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-muted uppercase tracking-wide">{{ __('messages.superadmin.dashboard.parents') }}</p>
                <p class="text-2xl font-bold text-navy mt-1">{{ $stats['total_parents'] }}</p>
                <p class="text-xs text-faint mt-1">{{ __('messages.superadmin.dashboard.registered') }}</p>
            </div>
        </x-card>
        <x-card>
            <div class="p-5">
                <p class="text-xs font-medium text-muted uppercase tracking-wide">{{ __('messages.superadmin.dashboard.players') }}</p>
                <p class="text-2xl font-bold text-navy mt-1">{{ $stats['active_players'] }}<span class="text-sm font-normal text-faint">/{{ $stats['total_players'] }}</span></p>
                <p class="text-xs text-faint mt-1">{{ __('messages.superadmin.dashboard.active_total') }}</p>
            </div>
        </x-card>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <x-card>
            <div class="p-5">
                <p class="text-sm font-medium text-muted mb-1">{{ __('messages.superadmin.dashboard.active_enrollments') }}</p>
                <p class="text-3xl font-bold text-navy">{{ $stats['enrollments'] }}</p>
            </div>
        </x-card>
        <x-card padding="p-0">
            <div class="px-6 py-4 border-b border-line">
                <p class="text-xs font-bold uppercase tracking-wide text-navy">{{ __('messages.superadmin.dashboard.quick_links') }}</p>
            </div>
            <div class="p-2">
                <a href="{{ route('superadmin.admins') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                    <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ __('messages.superadmin.nav.admin_accounts') }}
                </a>
                <a href="{{ route('superadmin.system-settings') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-ink hover:bg-off transition-colors">
                    <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    {{ __('messages.superadmin.nav.system_settings') }}
                </a>
            </div>
        </x-card>
    </div>

</div>
