@props(['href', 'active' => false, 'badge' => null])

@php
$path = ltrim(parse_url($href, PHP_URL_PATH), '/');
$isActive = $active || request()->is($path) || request()->is($path . '/*');
@endphp

<a href="{{ $href }}"
   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
          {{ $isActive
              ? 'bg-navy text-off font-semibold'
              : 'text-muted hover:bg-off hover:text-navy' }}">
    {{ $slot }}
    @if ($badge)
        <span class="ml-auto bg-[#DC2626] text-off text-[10px] font-bold rounded-full px-1.5 py-0.5 min-w-[18px] text-center">
            {{ $badge }}
        </span>
    @endif
</a>
