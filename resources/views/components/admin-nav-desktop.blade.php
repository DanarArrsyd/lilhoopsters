@php
    $isActive = fn(string ...$routes) => collect($routes)->contains(fn($r) => request()->routeIs($r));
@endphp

<nav class="hidden lg:flex items-center gap-1">
    <a href="{{ route('admin.dashboard') }}"
       class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-colors {{ $isActive('admin.dashboard') ? 'bg-navy/8 text-navy' : 'text-muted hover:text-navy hover:bg-off' }}">
        {{ __('messages.admin.nav.dashboard') }}
    </a>

    <x-nav-dropdown :label="__('messages.admin.section.people')" data-has-active="{{ $isActive('admin.parents','admin.players','admin.leads','admin.coaches','admin.members-import') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.parents') }}" :active="request()->routeIs('admin.parents')" :badge="$navBadges['parents'] ?: null">{{ __('messages.admin.nav.parents') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.players') }}" :active="request()->routeIs('admin.players')">{{ __('messages.admin.nav.players') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.leads') }}" :active="request()->routeIs('admin.leads')">{{ __('messages.admin.nav.leads') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.coaches') }}" :active="request()->routeIs('admin.coaches')">{{ __('messages.admin.nav.coaches') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.members-import') }}" :active="request()->routeIs('admin.members-import')">{{ __('messages.admin.nav.import_members') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.programs')" data-has-active="{{ $isActive('admin.locations','admin.programs','admin.packages','admin.schedules','admin.events') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.locations') }}" :active="request()->routeIs('admin.locations')">{{ __('messages.admin.nav.locations') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.programs') }}" :active="request()->routeIs('admin.programs')">{{ __('messages.admin.nav.programs') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.packages') }}" :active="request()->routeIs('admin.packages')">{{ __('messages.admin.nav.packages') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.schedules') }}" :active="request()->routeIs('admin.schedules')">{{ __('messages.admin.nav.schedules') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.events') }}" :active="request()->routeIs('admin.events')">{{ __('messages.admin.nav.events') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.operations')" data-has-active="{{ $isActive('admin.attendances','admin.leave-requests','admin.makeup-classes','admin.enrollments') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.attendances') }}" :active="request()->routeIs('admin.attendances')">{{ __('messages.admin.nav.attendances') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.leave-requests') }}" :active="request()->routeIs('admin.leave-requests')" :badge="$navBadges['leave_requests'] ?: null">{{ __('messages.admin.nav.leave_requests') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.makeup-classes') }}" :active="request()->routeIs('admin.makeup-classes')" :badge="$navBadges['makeup_classes'] ?: null">{{ __('messages.admin.nav.makeup_classes') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.enrollments') }}" :active="request()->routeIs('admin.enrollments')" :badge="$navBadges['enrollments'] ?: null">{{ __('messages.admin.nav.enrollments') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.finance')" data-has-active="{{ $isActive('admin.payments','admin.reports','admin.owner') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.payments') }}" :active="request()->routeIs('admin.payments')" :badge="$navBadges['payments'] ?: null">{{ __('messages.admin.nav.payments') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.reports') }}" :active="request()->routeIs('admin.reports')">{{ __('messages.admin.nav.reports') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.owner') }}" :active="request()->routeIs('admin.owner')">{{ __('messages.admin.nav.owner_insights') }}</x-sidebar-link>
    </x-nav-dropdown>

    <x-nav-dropdown :label="__('messages.admin.section.reports')" data-has-active="{{ $isActive('admin.report-cards','admin.news') ? 'true' : 'false' }}">
        <x-sidebar-link href="{{ route('admin.report-cards') }}" :active="request()->routeIs('admin.report-cards')" :badge="$navBadges['report_cards'] ?: null">{{ __('messages.admin.nav.report_cards') }}</x-sidebar-link>
        <x-sidebar-link href="{{ route('admin.news') }}" :active="request()->routeIs('admin.news')">{{ __('messages.admin.nav.news') }}</x-sidebar-link>
    </x-nav-dropdown>
</nav>
