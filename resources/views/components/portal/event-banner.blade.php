@props(['activeEvent'])

@if ($activeEvent)
    <div class="block mb-4 px-4 py-3 rounded-xl bg-navy text-off">
        <p class="text-sm font-semibold">{{ $activeEvent->name }}</p>
        <p class="text-xs opacity-80">{{ __('messages.portal.home.event_open') }}</p>
    </div>
@endif
