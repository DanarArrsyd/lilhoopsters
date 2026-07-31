<div class="flex flex-col min-h-[100dvh]">

    {{-- Topbar --}}
    <header class="h-14 bg-surface border-b border-line flex justify-between lg:grid lg:grid-cols-[1fr_auto_1fr] items-center px-4 gap-4 sticky top-0 z-30">
        <div class="flex items-center min-w-0">
            <img src="{{ asset('basket_logo.jpeg') }}" alt="Lil' Hoopsters" class="w-8 h-8 rounded-lg object-cover shrink-0">
        </div>
        <x-portal.top-nav />
        <div class="flex items-center justify-end gap-4">
            <livewire:locale-switcher />
            <livewire:notification-bell />
            <x-portal.avatar-menu />
        </div>
    </header>

    {{-- ── Scrollable content ── --}}
    <div class="flex-1 bg-off">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 pt-6 pb-24 lg:pb-10">

            @if ($children->isEmpty())
                <x-empty-state
                    :title="__('messages.portal.home.empty_title')"
                    :description="__('messages.portal.home.empty_desc')">
                    <x-slot name="action">
                        <x-btn href="{{ route('parent.players') }}" variant="add">{{ __('messages.portal.home.add_player') }}</x-btn>
                    </x-slot>
                </x-empty-state>
            @else
                <x-admin.page-header
                    :title="__('messages.dashboard.title')"
                    :subtitle="__('messages.portal.home.greeting', ['name' => auth()->user()->name])" />

                @if (session('error'))
                    <x-alert type="danger" class="mb-4">{{ session('error') }}</x-alert>
                @endif

                @if ($sectionFailed ?? false)
                    <div class="mb-4 px-4 py-3 rounded-xl bg-[#B91C1C]/10 text-[#B91C1C] text-sm flex items-center justify-between lg:max-w-2xl">
                        <span>{{ __('messages.portal.home.section_error') }}</span>
                        <button wire:click="$refresh" class="font-semibold underline shrink-0 ml-3">{{ __('messages.portal.home.retry') }}</button>
                    </div>
                @endif

                <x-portal.child-switcher :children="$children" :active-child-id="$activeChildId" />

                {{-- Full width, above the split: this is the question the page
                     exists to answer, so it gets the first line rather than the
                     smallest one inside the week card. --}}
                <div class="mb-6">
                    <x-portal.next-session :session="$nextSession" />
                </div>

                <div class="flex flex-col lg:flex-row gap-6 items-start">

                    {{-- LEFT: Weekly calendar --}}
                    <div class="w-full lg:w-72 shrink-0 lg:sticky lg:top-20">
                        <x-portal.week-card
                            :week-strip="$weekStrip"
                            :week-sessions="$weekSessions"
                            :show-calendar="$showCalendar"
                            :calendar="$calendar"
                            :selected-date="$selectedDate"
                            :selected-sessions="$selectedSessions" />
                    </div>

                    {{-- RIGHT: Main content --}}
                    <div class="flex-1 min-w-0">
                        <x-portal.event-banner :active-event="$activeEvent" />
                        <x-portal.quick-actions :show-qr="$showQr" :qr-svg="$qrSvg" :active-child="$child" />
                        <x-portal.class-list :classes="$classes" :classes-tab="$classesTab" />
                    </div>
                </div>
            @endif

        </div>{{-- /max-w-6xl --}}
    </div>{{-- /flex-1 bg-off --}}

    <x-portal.bottom-nav />

</div>{{-- /flex --}}
