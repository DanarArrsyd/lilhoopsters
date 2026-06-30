@props(['activeEvent'])

@if ($activeEvent)
    <a href="{{ route('parent.events') }}"
       class="block mb-4 px-4 py-3 rounded-xl bg-navy text-off flex items-center justify-between">
        <div>
            <p class="text-sm font-semibold">{{ $activeEvent->name }}</p>
            <p class="text-xs opacity-80">{{ __('messages.portal.home.event_open') }}</p>
        </div>
        <span class="text-xs font-semibold underline">{{ __('messages.portal.home.event_cta') }}</span>
    </a>
@endif
