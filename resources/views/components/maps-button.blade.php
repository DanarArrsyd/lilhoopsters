@props(['url' => null])

@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener"
       {{ $attributes->merge(['class' => 'inline-flex items-center justify-center text-navy/60 hover:text-navy transition-colors shrink-0']) }}
       title="{{ __('messages.common.open_in_maps') }}"
       aria-label="{{ __('messages.common.open_in_maps') }}"
       onclick="event.stopPropagation()">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </a>
@endif
