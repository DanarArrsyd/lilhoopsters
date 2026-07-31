<x-sidebar-link href="{{ route('admin.dashboard') }}" :active="request()->routeIs('admin.dashboard')">
    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
    </svg>
    {{ __('messages.admin.nav.dashboard') }}
</x-sidebar-link>

<x-sidebar-section :label="__('messages.admin.section.people')" :count="$navBadges['parents']">
    <x-sidebar-link href="{{ route('admin.parents') }}" :active="request()->routeIs('admin.parents')"
                    :badge="$navBadges['parents'] ?: null">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ __('messages.admin.nav.parents') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.players') }}" :active="request()->routeIs('admin.players')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="2" fill="none"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4c0 0-4 3-4 8s4 8 4 8"/>
        </svg>
        {{ __('messages.admin.nav.players') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.leads') }}" :active="request()->routeIs('admin.leads')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        {{ __('messages.admin.nav.leads') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.coaches') }}" :active="request()->routeIs('admin.coaches')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        {{ __('messages.admin.nav.coaches') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.members-import') }}" :active="request()->routeIs('admin.members-import')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        {{ __('messages.admin.nav.import_members') }}
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section :label="__('messages.admin.section.programs')">
    <x-sidebar-link href="{{ route('admin.locations') }}" :active="request()->routeIs('admin.locations')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        {{ __('messages.admin.nav.locations') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.programs') }}" :active="request()->routeIs('admin.programs')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        {{ __('messages.admin.nav.programs') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.packages') }}" :active="request()->routeIs('admin.packages')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
        </svg>
        {{ __('messages.admin.nav.packages') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        {{ __('messages.admin.nav.schedules') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.events') }}" :active="request()->routeIs('admin.events')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
        {{ __('messages.admin.nav.events') }}
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section :label="__('messages.admin.section.operations')" :count="$navBadges['leave_requests'] + $navBadges['makeup_classes'] + $navBadges['enrollments']">
    <x-sidebar-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
        </svg>
        {{ __('messages.admin.nav.attendances') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.leave-requests') }}" :active="request()->routeIs('admin.leave-requests')"
                    :badge="$navBadges['leave_requests'] ?: null">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        {{ __('messages.admin.nav.leave_requests') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.makeup-classes') }}" :active="request()->routeIs('admin.makeup-classes')"
                    :badge="$navBadges['makeup_classes'] ?: null">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        {{ __('messages.admin.nav.makeup_classes') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.enrollments') }}" :active="request()->routeIs('admin.enrollments')"
                    :badge="$navBadges['enrollments'] ?: null">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ __('messages.admin.nav.enrollments') }}
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section :label="__('messages.admin.section.finance')" :count="$navBadges['payments']">
    <x-sidebar-link href="{{ route('admin.payments') }}" :active="request()->routeIs('admin.payments')"
                    :badge="$navBadges['payments'] ?: null">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
        {{ __('messages.admin.nav.payments') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.verify-payment') }}" :active="request()->routeIs('admin.verify-payment')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
        </svg>
        {{ __('messages.admin.nav.verify_payment') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.reports') }}" :active="request()->routeIs('admin.reports')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        {{ __('messages.admin.nav.reports') }}
    </x-sidebar-link>
    @if (auth()->user()?->role?->name === 'super_admin')
        <x-sidebar-link href="{{ route('admin.owner') }}" :active="request()->routeIs('admin.owner')">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            {{ __('messages.admin.nav.owner_insights') }}
        </x-sidebar-link>
    @endif
</x-sidebar-section>

<x-sidebar-section :label="__('messages.admin.section.reports')" :count="$navBadges['report_cards']">
    <x-sidebar-link href="{{ route('admin.report-cards') }}" :active="request()->routeIs('admin.report-cards')"
                    :badge="$navBadges['report_cards'] ?: null">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        {{ __('messages.admin.nav.report_cards') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.news') }}" :active="request()->routeIs('admin.news')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m0 0h2a2 2 0 012 2v9a2 2 0 11-4 0V5a2 2 0 00-2-2H5"/>
        </svg>
        {{ __('messages.admin.nav.news') }}
    </x-sidebar-link>
    <x-sidebar-link href="{{ route('admin.audit-log') }}" :active="request()->routeIs('admin.audit-log')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Audit Log
    </x-sidebar-link>
</x-sidebar-section>

<x-sidebar-section :label="__('messages.admin.section.account')">
    <x-sidebar-link href="{{ route('admin.profile') }}" :active="request()->routeIs('admin.profile')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
        </svg>
        {{ __('messages.admin.nav.profile') }}
    </x-sidebar-link>
</x-sidebar-section>
